<?php

namespace App\Jobs;

use App\Enums\DerivativeType;
use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Services\SettingsService;
use App\Services\MediaOptimizationService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
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

    public int $tries = 1;

    private string $attemptToken;

    private ?string $expectedToken;

    private string $sourceFilename;

    public function __construct(public Media $media)
    {
        $this->attemptToken = (string) \Illuminate\Support\Str::uuid();
        $this->expectedToken = $media->processing_token;
        $this->sourceFilename = $media->filename;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->media->id))->dontRelease()];
    }

    public function handle(WatermarkService $watermarkService, WatermarkVerificationService $verificationService): void
    {
        $claimed = \Illuminate\Support\Facades\DB::transaction(function (): bool {
            $current = Media::query()->lockForUpdate()->find($this->media->id);
            if (! $current || $current->filename !== $this->sourceFilename ||
                $current->processing_token !== $this->expectedToken) {
                return false;
            }

            $current->processing_token = $this->attemptToken;
            $current->last_processing_status = 'processing';
            $current->last_processing_error = null;
            if (! $this->hasActiveDerivative($current)) {
                $current->processing_status = MediaProcessingStatus::PROCESSING;
            }
            $current->save();
            $this->media = $current;

            return true;
        });

        // A serialized duplicate or an older request cannot supersede this claim.
        // Explicit "Proses Ulang" creates a fresh job from the current record.
        if (! $claimed) {
            return;
        }

        $stagingDirectory = 'staging/'.$this->media->id.'/'.$this->attemptToken;

        try {
            $policy = app(\App\Services\MediaInputPolicy::class);
            if ($this->media->disk !== 'local' || $this->media->directory !== 'originals') {
                throw new \RuntimeException('Unsupported original storage location.');
            }
            $originalPath = $policy->originalPath('originals/'.$this->sourceFilename);
            $mimeType = $policy->inspect($originalPath, $this->sourceFilename);
            $originalChecksum = hash_file('sha256', $originalPath);

            // D03-B: never call the legacy regex PDF injector. Existing approved
            // derivatives remain intact; the PDF/Office publication contract is P3-C.
            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/heic'], true)) {
                $this->recordFailure('publication_format_deferred', InvisibleWatermarkStatus::UNSUPPORTED);
                return;
            }

            $disk = Storage::disk('local');
            if (! $disk->makeDirectory($stagingDirectory)) {
                throw new \RuntimeException('Cannot create private staging directory.');
            }
            $stagingFilename = $stagingDirectory.'/candidate.'.\App\Services\MediaInputPolicy::EXTENSIONS[$mimeType][0];
            $stagingPath = $disk->path($stagingFilename);
            if (! copy($originalPath, $stagingPath)) {
                throw new \RuntimeException('Cannot copy original to private staging.');
            }

            if ($mimeType === 'image/heic') {
                $convertedFilename = $stagingDirectory.'/converted.jpg';
                $convertedPath = $disk->path($convertedFilename);
                $process = new \Symfony\Component\Process\Process([
                    'heif-convert', '-q', '90', $stagingPath, $convertedPath,
                ]);
                $process->setTimeout(max(10, min(600, (int) SettingsService::get('processing_timeout', 120))));
                $process->mustRun();
                if ($policy->inspect($convertedPath, publication: true) !== 'image/jpeg') {
                    throw new \RuntimeException('HEIC conversion did not produce a valid JPEG.');
                }
                $this->fixOrientation($convertedPath, $originalPath);
                $stagingFilename = $convertedFilename;
                $stagingPath = $convertedPath;
                $mimeType = 'image/jpeg';
            }

            app(MediaOptimizationService::class)->optimize($stagingPath, $mimeType);

            if (SettingsService::get('enable_visible_watermark', false)) {
                $watermarkService->applyVisibleWatermark($stagingPath);
            }

            $payload = $verificationService->generatePayload($this->media, DerivativeType::PUBLIC);
            if (! $watermarkService->injectInvisibleIdentifier($stagingPath, $mimeType, $payload)) {
                $this->recordFailure('watermark_injection_failed');
                return;
            }

            if ($policy->inspect($stagingPath, publication: true) !== $mimeType) {
                throw new \RuntimeException('Processed media format changed unexpectedly.');
            }

            // The checksum is intentionally calculated only after all visible
            // and invisible watermark bytes have been written. It therefore
            // identifies the exact candidate the verifier is about to inspect.
            $checksum = hash_file('sha256', $stagingPath);
            $dimensions = getimagesize($stagingPath);
            if ($checksum === false || $dimensions === false) {
                throw new \RuntimeException('Cannot inspect completed candidate.');
            }

            $stagingDerivative = new MediaDerivative([
                'media_id' => $this->media->id,
                'derivative_type' => DerivativeType::PUBLIC,
                'filename' => $stagingFilename,
                'disk' => 'local',
                'size' => filesize($stagingPath),
                'mime_type' => $mimeType,
                'checksum' => $checksum,
                'width' => $dimensions[0],
                'height' => $dimensions[1],
            ]);
            if (! $verificationService->verifyDerivative($stagingDerivative, $this->media)) {
                $this->recordFailure('watermark_verification_failed');
                return;
            }

            $extension = \App\Services\MediaInputPolicy::EXTENSIONS[$mimeType][0];
            $thumbnailPath = $disk->path($stagingDirectory.'/thumbnail.'.$extension);
            app(MediaOptimizationService::class)->thumbnail($stagingPath, $thumbnailPath, $mimeType);
            $thumbnailPayload = $verificationService->generatePayload($this->media, DerivativeType::THUMBNAIL);
            if (! $watermarkService->injectInvisibleIdentifier($thumbnailPath, $mimeType, $thumbnailPayload)
                || $policy->inspect($thumbnailPath, publication: true) !== $mimeType) {
                $this->recordFailure('thumbnail_verification_failed');
                return;
            }
            $thumbnailChecksum = hash_file('sha256', $thumbnailPath);
            $thumbnailDimensions = getimagesize($thumbnailPath);
            if ($thumbnailChecksum === false || $thumbnailDimensions === false) {
                throw new \RuntimeException('Cannot inspect thumbnail candidate.');
            }
            $thumbnailDerivative = new MediaDerivative([
                'media_id' => $this->media->id,
                'derivative_type' => DerivativeType::THUMBNAIL,
                'filename' => $stagingDirectory.'/thumbnail.'.$extension,
                'disk' => 'local',
                'size' => filesize($thumbnailPath),
                'mime_type' => $mimeType,
                'checksum' => $thumbnailChecksum,
                'width' => $thumbnailDimensions[0],
                'height' => $thumbnailDimensions[1],
            ]);
            if (! $verificationService->verifyDerivative($thumbnailDerivative, $this->media)) {
                $this->recordFailure('thumbnail_verification_failed');
                return;
            }
            if ($originalChecksum === false || hash_file('sha256', $originalPath) !== $originalChecksum) {
                throw new \RuntimeException('Original changed during processing.');
            }

            \Illuminate\Support\Facades\DB::transaction(function () use (
                $stagingPath, $thumbnailPath, $thumbnailChecksum, $thumbnailDimensions,
                $mimeType, $checksum, $dimensions, $originalChecksum
            ): void {
                $current = Media::query()->lockForUpdate()->find($this->media->id);
                if (! $current || $current->processing_token !== $this->attemptToken ||
                    $current->filename !== $this->sourceFilename) {
                    return;
                }

                $existing = $current->derivatives()->where('derivative_type', DerivativeType::PUBLIC)->first();
                $existingThumbnail = $current->derivatives()->where('derivative_type', DerivativeType::THUMBNAIL)->first();
                $privateFilename = 'derivatives/'.$current->id.'/'.$this->attemptToken.'.'.\App\Services\MediaInputPolicy::EXTENSIONS[$mimeType][0];
                $privateThumbnailFilename = 'derivatives/'.$current->id.'/'.$this->attemptToken.'-thumbnail.'.\App\Services\MediaInputPolicy::EXTENSIONS[$mimeType][0];
                $privateDisk = Storage::disk('local');
                if (config('filesystems.disks.local.driver') !== 'local' ||
                    ! $privateDisk->makeDirectory(dirname($privateFilename))) {
                    throw new \RuntimeException('Cannot prepare private derivative directory.');
                }
                $privatePath = $privateDisk->path($privateFilename);

                // The generation stays outside the web root. A rollback can
                // therefore leave only a private, reconcilable orphan.
                $sourceStat = stat($stagingPath);
                $targetStat = stat(dirname($privatePath));
                if (! $sourceStat || ! $targetStat || $sourceStat['dev'] !== $targetStat['dev'] ||
                    file_exists($privatePath) || ! rename($stagingPath, $privatePath)) {
                    throw new \RuntimeException('Cannot atomically finalize the private candidate on this storage layout.');
                }
                $privateThumbnailPath = $privateDisk->path($privateThumbnailFilename);
                $thumbnailStat = stat($thumbnailPath);
                if (! $thumbnailStat || $thumbnailStat['dev'] !== $targetStat['dev'] ||
                    file_exists($privateThumbnailPath) || ! rename($thumbnailPath, $privateThumbnailPath)) {
                    throw new \RuntimeException('Cannot atomically finalize the private thumbnail on this storage layout.');
                }

                $metadata = $current->metadata ?? [];
                if ($existing) {
                    // Retain both bytes and their identity. No age-based purge
                    // or automatic deletion is authorized by D08.
                    $metadata['retired_derivatives'][] = [
                        'disk' => $existing->disk,
                        'filename' => $existing->filename,
                        'checksum' => $existing->checksum,
                    ];
                }
                if ($existingThumbnail) {
                    $metadata['retired_derivatives'][] = [
                        'disk' => $existingThumbnail->disk,
                        'filename' => $existingThumbnail->filename,
                        'checksum' => $existingThumbnail->checksum,
                    ];
                }
                $current->derivatives()->updateOrCreate(
                    ['derivative_type' => DerivativeType::PUBLIC],
                    [
                        'filename' => $privateFilename, 'disk' => 'local',
                        'size' => filesize($privatePath), 'mime_type' => $mimeType,
                        'checksum' => $checksum, 'width' => $dimensions[0], 'height' => $dimensions[1],
                    ],
                );
                $current->derivatives()->updateOrCreate(
                    ['derivative_type' => DerivativeType::THUMBNAIL],
                    [
                        'filename' => $privateThumbnailFilename, 'disk' => 'local',
                        'size' => filesize($privateThumbnailPath), 'mime_type' => $mimeType,
                        'checksum' => $thumbnailChecksum,
                        'width' => $thumbnailDimensions[0], 'height' => $thumbnailDimensions[1],
                    ],
                );
                $current->metadata = $metadata;
                $current->processing_status = MediaProcessingStatus::COMPLETED;
                $current->invisible_watermark_status = InvisibleWatermarkStatus::VERIFIED;
                $current->checksum = $originalChecksum;
                $current->last_processing_status = 'completed';
                $current->last_processing_error = null;
                $current->save();
                \App\Services\AuditLogService::log('media_processing_completed', $current);
            });
        } catch (\Throwable $exception) {
            // Keep the upload/edit transaction usable and persist the failure.
            // Do not log exception messages containing filesystem/config details.
            $this->recordFailure('processing_failed');
            \Illuminate\Support\Facades\Log::warning('Media processing attempt failed.', [
                'media_id' => $this->media->id,
                'attempt' => $this->attemptToken,
                'exception_type' => $exception::class,
            ]);
        } finally {
            // Only this job's private staging namespace; never the original,
            // active pointer, or any previous public generation.
            try {
                if (! Storage::disk('local')->deleteDirectory($stagingDirectory)) {
                    \Illuminate\Support\Facades\Log::warning('Media staging cleanup pending.', [
                        'media_id' => $this->media->id, 'attempt' => $this->attemptToken,
                    ]);
                }
            } catch (\Throwable) {
                \Illuminate\Support\Facades\Log::warning('Media staging cleanup pending.', [
                    'media_id' => $this->media->id, 'attempt' => $this->attemptToken,
                ]);
            }
        }
    }

    private function hasActiveDerivative(Media $media): bool
    {
        if ($media->processing_status !== MediaProcessingStatus::COMPLETED ||
            $media->invisible_watermark_status !== InvisibleWatermarkStatus::VERIFIED) {
            return false;
        }

        $derivative = $media->derivatives()->where('derivative_type', DerivativeType::PUBLIC)->first();

        return $derivative && Storage::disk($derivative->disk)->exists($derivative->filename);
    }

    private function recordFailure(string $reason, InvisibleWatermarkStatus $watermarkStatus = InvisibleWatermarkStatus::FAILED): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($reason, $watermarkStatus): void {
            $current = Media::query()->lockForUpdate()->find($this->media->id);
            if (! $current || $current->processing_token !== $this->attemptToken) {
                return;
            }

            $current->last_processing_status = 'failed';
            $current->last_processing_error = $reason;
            if (! $this->hasActiveDerivative($current)) {
                $current->processing_status = MediaProcessingStatus::FAILED;
                $current->invisible_watermark_status = $watermarkStatus;
            }
            $current->save();
            \App\Services\AuditLogService::log('media_processing_failed', $current, null, ['reason' => $reason]);
        });
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
