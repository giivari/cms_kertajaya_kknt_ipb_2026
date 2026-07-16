<?php

use App\Jobs\ProcessMediaJob;
use App\Services\MediaProcessingService;
use App\Services\SettingsService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('changes pixels when visible watermark is enabled', function () {
    Storage::fake('private');
    Storage::fake('public');
    config(['watermark.signing_key' => 'test']);
    SettingsService::set('enable_visible_watermark', true);

    $tempPath = tempnam(sys_get_temp_dir(), 'test');
    $image = imagecreatetruecolor(100, 100);
    $originalColor = imagecolorallocate($image, 0, 0, 0);
    imagefill($image, 0, 0, $originalColor);
    imagejpeg($image, $tempPath);

    $originalImage = imagecreatefromjpeg($tempPath);
    $originalColorAt50 = imagecolorat($originalImage, 50, 50);

    $file = new UploadedFile($tempPath, 'test.jpg', 'image/jpeg', null, true);

    $service = new MediaProcessingService;
    $media = $service->handleUpload($file, ['original_filename' => 'Test Image']);

    $job = new ProcessMediaJob($media);
    $job->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));

    $media->refresh();
    $publicPath = Storage::disk('public')->path('media/'.$media->filename);

    $newImage = imagecreatefromjpeg($publicPath);

    $changed = false;
    for ($y = 0; $y < 100; $y++) {
        for ($x = 0; $x < 100; $x++) {
            if (imagecolorat($newImage, $x, $y) !== $originalColorAt50) {
                $changed = true;
                break 2;
            }
        }
    }

    expect($changed)->toBeTrue();
});

it('does not change pixels when visible watermark is disabled', function () {
    Storage::fake('private');
    Storage::fake('public');
    config(['watermark.signing_key' => 'test']);
    SettingsService::set('enable_visible_watermark', false);

    $tempPath = tempnam(sys_get_temp_dir(), 'test');
    $image = imagecreatetruecolor(100, 100);
    $originalColor = imagecolorallocate($image, 0, 0, 0);
    imagefill($image, 0, 0, $originalColor);
    imagejpeg($image, $tempPath);

    $originalImage = imagecreatefromjpeg($tempPath);
    $originalColorAt50 = imagecolorat($originalImage, 50, 50);

    $file = new UploadedFile($tempPath, 'test.jpg', 'image/jpeg', null, true);

    $service = new MediaProcessingService;
    $media = $service->handleUpload($file, ['original_filename' => 'Test Image']);

    // Mock invisible watermark injection so it doesn't fail, but let visible watermark do its thing (or not)
    $mockWatermarkService = Mockery::mock(WatermarkService::class)->makePartial();
    $mockWatermarkService->shouldReceive('injectInvisibleIdentifier')->andReturn(true);

    $job = new ProcessMediaJob($media);
    $job->handle($mockWatermarkService, app(WatermarkVerificationService::class));

    $media->refresh();
    $publicPath = Storage::disk('public')->path('media/'.$media->filename);

    $newImage = imagecreatefromjpeg($publicPath);
    $newColorAt50 = imagecolorat($newImage, 50, 50);

    expect($newColorAt50)->toBe($originalColorAt50);
});
