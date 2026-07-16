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
        if ($this->media->processing_status === MediaProcessingStatus::COMPLETED) {
            return; // Idempotent: already completed
        }

        $this->media->update(['processing_status' => MediaProcessingStatus::PROCESSING]);

        try {
            $originalPath = Storage::disk('private')->path('originals/'.$this->media->filename);

            // Generate staging derivative
            $stagingFilename = 'staging/'.$this->media->filename;
            Storage::disk('private')->makeDirectory('staging');
            $stagingPath = Storage::disk('private')->path($stagingFilename);

            // For MVP, copy the file. (Resize/optimize would happen here)
            copy($originalPath, $stagingPath);

            // Apply visible watermark if image and enabled
            if (str_starts_with($this->media->mime_type, 'image/') && SettingsService::get('enable_visible_watermark', false)) {
                $watermarkService->applyVisibleWatermark($stagingPath);
            }

            // Inject invisible watermark
            $payload = $verificationService->generatePayload($this->media, DerivativeType::PUBLIC);

            $watermarkSuccess = $watermarkService->injectInvisibleIdentifier($stagingPath, $this->media->mime_type, $payload);

            if (! $watermarkSuccess) {
                Storage::disk('private')->delete($stagingFilename);
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
                'disk' => 'private',
                'size' => filesize($stagingPath),
                'mime_type' => $this->media->mime_type,
            ]);

            $isVerified = $verificationService->verifyDerivative($stagingDerivative, $this->media);

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

                Storage::disk('private')->delete($stagingFilename);

                $this->media->update([
                    'processing_status' => MediaProcessingStatus::COMPLETED,
                    'invisible_watermark_status' => InvisibleWatermarkStatus::VERIFIED,
                    'checksum' => hash_file('sha256', $originalPath),
                ]);
            } else {
                Storage::disk('private')->delete($stagingFilename);
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
}
