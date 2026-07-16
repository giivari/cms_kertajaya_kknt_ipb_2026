<?php

namespace Tests\Feature;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Jobs\ProcessMediaJob;
use App\Models\WatermarkVerificationLog;
use App\Services\MediaProcessingService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_processing_pipeline_success()
    {
        Storage::fake('private');
        Storage::fake('public');
        Queue::fake();
        config(['watermark.signing_key' => 'test-signing-key']);

        $image = imagecreatetruecolor(100, 100);
        $tempPath = tempnam(sys_get_temp_dir(), 'test_img').'.jpg';
        imagejpeg($image, $tempPath);
        imagedestroy($image);

        $file = new UploadedFile($tempPath, 'test.jpg', 'image/jpeg', null, true);

        $service = new MediaProcessingService;
        $media = $service->handleUpload($file, ['original_filename' => 'Test Image']);

        $this->assertEquals(MediaProcessingStatus::PENDING, $media->processing_status);
        $this->assertEquals('private', $media->disk);
        Storage::disk('private')->assertExists('originals/'.$media->filename);

        // Run the job synchronously
        $job = new ProcessMediaJob($media);
        $job->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));

        $media->refresh();

        if ($media->processing_status->value === 'failed') {
            dump('Verification logs:', WatermarkVerificationLog::all()->toArray());
        }

        $this->assertEquals(MediaProcessingStatus::COMPLETED, $media->processing_status);
        $this->assertEquals(InvisibleWatermarkStatus::VERIFIED, $media->invisible_watermark_status);

        $derivatives = $media->derivatives;
        $this->assertCount(1, $derivatives);
        $this->assertEquals('public', $derivatives->first()->disk);

        Storage::disk('public')->assertExists($derivatives->first()->filename);

        $logs = $media->verificationLogs;
        $this->assertCount(1, $logs);
        $this->assertTrue($logs->first()->is_verified);
    }

    public function test_media_pipeline_fails_closed_and_keeps_private()
    {
        Storage::fake('public');
        Storage::fake('private');
        config(['watermark.signing_key' => 'test']);

        // Use a mock to force verification failure
        $mockVerification = \Mockery::mock(WatermarkVerificationService::class)->makePartial();
        $mockVerification->shouldReceive('verifyDerivative')->andReturn(false);
        $this->app->instance(WatermarkVerificationService::class, $mockVerification);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_img');
        $image = imagecreatetruecolor(10, 10);
        imagejpeg($image, $tempPath);
        $file = new UploadedFile($tempPath, 'test.jpg', 'image/jpeg', null, true);

        $service = new MediaProcessingService;
        $media = $service->handleUpload($file, ['original_filename' => 'Test Image']);

        $job = new ProcessMediaJob($media);
        $job->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));

        $media->refresh();

        $this->assertEquals(MediaProcessingStatus::FAILED, $media->processing_status);
        $this->assertEquals(InvisibleWatermarkStatus::FAILED, $media->invisible_watermark_status);
        Storage::disk('public')->assertMissing('media/'.$media->filename);
        Storage::disk('private')->assertMissing('staging/'.$media->filename);
        Storage::disk('private')->assertExists('originals/'.$media->filename);
    }
}
