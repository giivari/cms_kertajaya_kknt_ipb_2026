<?php

namespace App\Jobs;

use App\Enums\DerivativeType;
use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Services\SettingsService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Media $media) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->media->id))->dontRelease()];
    }

    public function handle(WatermarkService $watermarkService, WatermarkVerificationService $verificationService): void
    {
        // Allow reprocessing by removing the idempotency check for COMPLETED status.
        // This is needed so that the 'Proses Ulang' button works when watermark settings change.
        $this->media->update(['processing_status' => MediaProcessingStatus::PROCESSING]);

        try {
            $originalPath = Storage::disk('local')->path('originals/'.$this->media->filename);

            // Generate staging derivative
            $stagingFilename = 'staging/'.$this->media->filename;
            Storage::disk('local')->makeDirectory('staging');
            $stagingPath = Storage::disk('local')->path($stagingFilename);

            // For MVP, copy the file. (Resize/optimize would happen here)
            copy($originalPath, $stagingPath);

            // Auto-convert HEIC to JPG if the server has heif-convert installed
            if ($this->media->mime_type === 'image/heic') {
                $newStagingPath = preg_replace('/\.heic$/i', '.jpg', $stagingPath);
                $returnCode = 0;
                exec("heif-convert -q 90 " . escapeshellarg($stagingPath) . " " . escapeshellarg($newStagingPath) . " 2>/dev/null", $output, $returnCode);
                
                if ($returnCode === 0 && file_exists($newStagingPath)) {
                    $this->fixOrientation($newStagingPath, $originalPath);
                    
                    unlink($stagingPath);
                    $stagingPath = $newStagingPath;
                    $stagingFilename = preg_replace('/\.heic$/i', '.jpg', $stagingFilename);
                    
                    $this->media->mime_type = 'image/jpeg';
                    $this->media->filename = preg_replace('/\.heic$/i', '.jpg', $this->media->filename);
                    $this->media->extension = 'jpg';
                    $this->media->save();
                    
                    $newOriginalPath = preg_replace('/\.heic$/i', '.jpg', $originalPath);
                    copy($stagingPath, $newOriginalPath);
                    unlink($originalPath);
                    $originalPath = $newOriginalPath;
                }
            }

            // Apply visible watermark if image and enabled
            $supportedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $isSupported = in_array($this->media->mime_type, $supportedMimes);

            if (str_starts_with($this->media->mime_type, 'image/') && $isSupported && SettingsService::get('enable_visible_watermark', false)) {
                $watermarkService->applyVisibleWatermark($stagingPath);
            }

            if ($isSupported) {
                // Inject invisible watermark
                $payload = $verificationService->generatePayload($this->media, DerivativeType::PUBLIC);

                $watermarkSuccess = $watermarkService->injectInvisibleIdentifier($stagingPath, $this->media->mime_type, $payload);

                if (! $watermarkSuccess) {
                    Storage::disk('local')->delete($stagingFilename);
                    $this->media->update([
                        'invisible_watermark_status' => InvisibleWatermarkStatus::FAILED,
                        'processing_status' => MediaProcessingStatus::FAILED,
                    ]);

                    return;
                }

                // Verify on staging FIRST
                $stagingDerivative = new MediaDerivative([
                    'media_id' => $this->media->id,
                    'derivative_type' => DerivativeType::PUBLIC,
                    'filename' => $stagingFilename,
                    'disk' => 'local',
                    'size' => filesize($stagingPath),
                    'mime_type' => $this->media->mime_type,
                ]);

                $isVerified = $verificationService->verifyDerivative($stagingDerivative, $this->media);
            } else {
                $isVerified = true;
            }

            if ($isVerified) {
                // Verification passed! Move to public disk
                $publicFilename = 'media/'.$this->media->filename;
                Storage::disk('public')->makeDirectory('media');

                // Idempotency: delete old derivative if it exists
                $existingDerivative = MediaDerivative::where('media_id', $this->media->id)
                    ->where('derivative_type', DerivativeType::PUBLIC)
                    ->first();

                if ($existingDerivative) {
                    Storage::disk($existingDerivative->disk)->delete($existingDerivative->filename);
                    $existingDerivative->delete();
                }

                Storage::disk('public')->put($publicFilename, file_get_contents($stagingPath));
                $publicPath = Storage::disk('public')->path($publicFilename);

                $checksum = hash_file('sha256', $publicPath);

                $derivative = MediaDerivative::create([
                    'media_id' => $this->media->id,
                    'derivative_type' => DerivativeType::PUBLIC,
                    'filename' => $publicFilename,
                    'disk' => 'public',
                    'size' => filesize($publicPath),
                    'mime_type' => $this->media->mime_type,
                    'checksum' => $checksum,
                ]);

                Storage::disk('local')->delete($stagingFilename);

                $this->media->update([
                    'processing_status' => MediaProcessingStatus::COMPLETED,
                    'invisible_watermark_status' => $isSupported ? InvisibleWatermarkStatus::VERIFIED : InvisibleWatermarkStatus::UNSUPPORTED,
                    'checksum' => hash_file('sha256', $originalPath),
                ]);
            } else {
                Storage::disk('local')->delete($stagingFilename);
                $this->media->update([
                    'processing_status' => MediaProcessingStatus::FAILED,
                    'invisible_watermark_status' => InvisibleWatermarkStatus::FAILED,
                ]);
            }

        } catch (Exception $e) {
            $this->media->update(['processing_status' => MediaProcessingStatus::FAILED]);
            throw $e;
        }
    }

    protected function fixOrientation(string $jpgPath, string $originalHeicPath): void
    {
        // heif-convert strips EXIF, so we must read orientation from the original HEIC
        $output = [];
        $returnCode = 0;
        exec("exiftool -Orientation -n -S " . escapeshellarg($originalHeicPath) . " 2>/dev/null", $output, $returnCode);
        
        $orientation = 0;
        if ($returnCode === 0 && isset($output[0])) {
            if (preg_match('/^Orientation:\s*(\d+)/i', $output[0], $matches)) {
                $orientation = (int)$matches[1];
            }
        }

        if ($orientation > 1) {
            $image = @imagecreatefromjpeg($jpgPath);
            if ($image) {
                $rotated = null;
                switch ($orientation) {
                    case 3:
                        $rotated = imagerotate($image, 180, 0);
                        break;
                    case 6: // Rotate 90 CW
                        $rotated = imagerotate($image, -90, 0);
                        break;
                    case 8: // Rotate 90 CCW
                        $rotated = imagerotate($image, 90, 0);
                        break;
                }
                
                if ($rotated) {
                    imagejpeg($rotated, $jpgPath, 95);
                    imagedestroy($rotated);
                }
                imagedestroy($image);
            }
        }
    }
}
