<?php

namespace Tests\Feature;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_processing_publishes_only_a_verified_private_generation(): void
    {
        config(['watermark.signing_key' => 'disposable-pipeline-test-key']);
        [$media, $original] = $this->original();

        (new ProcessMediaJob($media))->handle(
            app(WatermarkService::class), app(WatermarkVerificationService::class),
        );

        $processed = $media->fresh();
        $derivative = $processed->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole();
        $this->assertSame(MediaProcessingStatus::COMPLETED, $processed->processing_status);
        $this->assertSame(InvisibleWatermarkStatus::VERIFIED, $processed->invisible_watermark_status);
        $this->assertSame('local', $derivative->disk);
        $this->assertStringStartsWith('derivatives/'.$media->id.'/', $derivative->filename);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($derivative->filename)), $derivative->checksum);
        $this->assertTrue(app(WatermarkVerificationService::class)->verifyDerivative($derivative, $processed));
        $this->assertSame($original, Storage::disk('local')->get('originals/'.$media->filename));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_verification_failure_keeps_original_private_and_candidate_unpublished(): void
    {
        config(['watermark.signing_key' => 'disposable-pipeline-test-key']);
        [$media, $original] = $this->original();
        $verification = \Mockery::mock(WatermarkVerificationService::class)->makePartial();
        $verification->shouldReceive('verifyDerivative')->once()->andReturn(false);

        (new ProcessMediaJob($media))->handle(app(WatermarkService::class), $verification);

        $processed = $media->fresh();
        $this->assertSame(MediaProcessingStatus::FAILED, $processed->processing_status);
        $this->assertSame(InvisibleWatermarkStatus::FAILED, $processed->invisible_watermark_status);
        $this->assertSame(0, $processed->derivatives()->count());
        $this->assertSame($original, Storage::disk('local')->get('originals/'.$media->filename));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function original(): array
    {
        $image = imagecreatetruecolor(40, 40);
        imagefilledrectangle($image, 0, 0, 39, 39, imagecolorallocate($image, 20, 100, 180));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        $filename = 'pipeline-'.uniqid().'.png';
        Storage::disk('local')->put('originals/'.$filename, $bytes);
        $media = Media::create([
            'disk' => 'local', 'directory' => 'originals', 'filename' => $filename,
            'original_filename' => 'fixture.png', 'mime_type' => 'image/png', 'extension' => 'png',
            'size' => strlen($bytes), 'width' => 40, 'height' => 40,
            'checksum' => hash('sha256', $bytes), 'metadata' => [],
            'processing_status' => MediaProcessingStatus::PENDING,
            'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
        ]);

        return [$media, $bytes];
    }
}
