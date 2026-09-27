<?php

namespace Tests\Feature;

use App\Enums\DerivativeType;
use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Services\SettingsService;
use App\Services\MediaOptimizationService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerApprovedMediaOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_original_is_unchanged_and_both_optimized_generations_are_verified_and_controlled(): void
    {
        config(['watermark.signing_key' => 'disposable-optimization-key']);
        SettingsService::setMany(['optimized_image_width' => 800, 'optimized_image_height' => 800]);

        $image = imagecreatetruecolor(1600, 900);
        imagefilledrectangle($image, 0, 0, 1599, 899, imagecolorallocate($image, 20, 90, 180));
        ob_start();
        imagepng($image);
        $original = (string) ob_get_clean();
        imagedestroy($image);

        Storage::disk('local')->put('originals/optimization.png', $original);
        $media = Media::create([
            'disk' => 'local', 'directory' => 'originals', 'filename' => 'optimization.png',
            'original_filename' => 'optimization.png', 'mime_type' => 'image/png', 'extension' => 'png',
            'size' => strlen($original), 'width' => 1600, 'height' => 900,
            'checksum' => hash('sha256', $original), 'metadata' => [],
            'processing_status' => MediaProcessingStatus::PENDING,
            'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
        ]);

        (new ProcessMediaJob($media))->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));
        $media = $media->fresh()->load('derivatives');
        $this->assertSame(MediaProcessingStatus::COMPLETED, $media->processing_status);
        $this->assertSame($original, Storage::disk('local')->get('originals/optimization.png'));

        foreach ([[DerivativeType::PUBLIC, [800, 450]], [DerivativeType::THUMBNAIL, [480, 270]]] as [$type, $expected]) {
            $derivative = $media->derivatives->firstWhere('derivative_type', $type);
            $this->assertNotNull($derivative);
            $this->assertSame($expected, [$derivative->width, $derivative->height]);
            $this->assertSame($derivative->checksum, hash_file('sha256', Storage::disk('local')->path($derivative->filename)));
            $this->assertTrue(app(WatermarkVerificationService::class)->verifyDerivative($derivative, $media));
        }

        $this->get(route('media.derivative', $media))->assertOk();
        $this->get(route('media.derivative', ['media' => $media, 'variant' => 'thumbnail']))->assertOk();
        $media->delete();
        $this->get(route('media.derivative', ['media' => $media->id, 'variant' => 'thumbnail']))->assertNotFound();
    }

    public function test_small_jpeg_and_webp_do_not_upscale_and_verify_after_encoding(): void
    {
        config(['watermark.signing_key' => 'disposable-optimization-key']);
        SettingsService::setMany(['optimized_image_width' => 800, 'optimized_image_height' => 800]);
        foreach (['jpeg' => 'jpg', 'webp' => 'webp'] as $format => $extension) {
            $image = imagecreatetruecolor(640, 360);
            imagefilledrectangle($image, 0, 0, 639, 359, imagecolorallocate($image, 20, 90, 180));
            ob_start();
            $format === 'jpeg' ? imagejpeg($image) : imagewebp($image);
            $original = (string) ob_get_clean();
            imagedestroy($image);
            $filename = 'optimization-small.'.$extension;
            Storage::disk('local')->put('originals/'.$filename, $original);
            $media = Media::create([
                'disk' => 'local', 'directory' => 'originals', 'filename' => $filename,
                'original_filename' => $filename, 'mime_type' => 'image/'.$format, 'extension' => $extension,
                'size' => strlen($original), 'width' => 640, 'height' => 360,
                'checksum' => hash('sha256', $original), 'metadata' => [],
                'processing_status' => MediaProcessingStatus::PENDING,
                'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
            ]);
            (new ProcessMediaJob($media))->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));
            $active = $media->fresh()->derivatives()->where('derivative_type', DerivativeType::PUBLIC)->sole();
            $this->assertSame([640, 360], [$active->width, $active->height]);
            $this->assertSame($original, Storage::disk('local')->get('originals/'.$filename));
            $this->assertTrue(app(WatermarkVerificationService::class)->verifyDerivative($active, $media->fresh()));
        }
    }

    public function test_optimization_failure_preserves_previous_approved_generation(): void
    {
        config(['watermark.signing_key' => 'disposable-optimization-key']);
        $image = imagecreatetruecolor(40, 40);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        Storage::disk('local')->put('originals/failure.png', $bytes);
        $media = Media::create([
            'disk' => 'local', 'directory' => 'originals', 'filename' => 'failure.png',
            'original_filename' => 'failure.png', 'mime_type' => 'image/png', 'extension' => 'png',
            'size' => strlen($bytes), 'width' => 40, 'height' => 40,
            'checksum' => hash('sha256', $bytes), 'metadata' => [],
            'processing_status' => MediaProcessingStatus::PENDING,
            'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
        ]);
        (new ProcessMediaJob($media))->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));
        $previous = $media->fresh()->derivatives()->where('derivative_type', DerivativeType::PUBLIC)->sole()->filename;
        $this->mock(MediaOptimizationService::class)
            ->shouldReceive('optimize')->once()->andThrow(new \RuntimeException('test-only optimization failure'));
        (new ProcessMediaJob($media->fresh()))->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));
        $this->assertSame($previous, $media->fresh()->derivatives()->where('derivative_type', DerivativeType::PUBLIC)->sole()->filename);
        $this->assertSame(MediaProcessingStatus::COMPLETED, $media->fresh()->processing_status);
        $this->get(route('media.derivative', $media))->assertOk();
    }
}
