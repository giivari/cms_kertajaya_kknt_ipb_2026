<?php

use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use App\Services\SettingsService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function visibleWatermarkFixture(): array
{
    $image = imagecreatetruecolor(120, 120);
    imagefilledrectangle($image, 0, 0, 119, 119, imagecolorallocate($image, 20, 20, 20));
    ob_start();
    imagepng($image);
    $bytes = (string) ob_get_clean();
    imagedestroy($image);
    $filename = 'visible-'.uniqid().'.png';
    Storage::disk('local')->put('originals/'.$filename, $bytes);
    $media = Media::create([
        'disk' => 'local', 'directory' => 'originals', 'filename' => $filename,
        'original_filename' => 'fixture.png', 'mime_type' => 'image/png', 'extension' => 'png',
        'size' => strlen($bytes), 'width' => 120, 'height' => 120,
        'checksum' => hash('sha256', $bytes), 'metadata' => [],
        'processing_status' => 'pending', 'invisible_watermark_status' => 'pending',
    ]);

    return [$media, $bytes];
}

function visibleWatermarkPixels(string $path): array
{
    $image = imagecreatefrompng($path);
    $pixels = [];
    for ($y = 0; $y < 120; $y++) {
        for ($x = 0; $x < 120; $x++) {
            $pixels[] = imagecolorat($image, $x, $y);
        }
    }
    imagedestroy($image);

    return $pixels;
}

it('changes image pixels when the visible watermark setting is enabled', function () {
    config(['watermark.signing_key' => 'disposable-visible-test-key']);
    SettingsService::set('enable_visible_watermark', true);
    SettingsService::set('watermark_text', 'TEST');
    SettingsService::set('watermark_position', 'center');
    [$media] = visibleWatermarkFixture();
    $originalPath = Storage::disk('local')->path('originals/'.$media->filename);
    $before = visibleWatermarkPixels($originalPath);

    (new ProcessMediaJob($media))->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));

    $active = $media->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole();
    $after = visibleWatermarkPixels(Storage::disk('local')->path($active->filename));
    expect($after)->not->toEqual($before);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('preserves image pixels when the visible watermark setting is disabled', function () {
    config(['watermark.signing_key' => 'disposable-visible-test-key']);
    SettingsService::set('enable_visible_watermark', false);
    [$media] = visibleWatermarkFixture();
    $originalPath = Storage::disk('local')->path('originals/'.$media->filename);
    $before = visibleWatermarkPixels($originalPath);

    (new ProcessMediaJob($media))->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));

    $active = $media->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole();
    $after = visibleWatermarkPixels(Storage::disk('local')->path($active->filename));
    expect($after)->toEqual($before);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});
