<?php

use App\Enums\DerivativeType;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Support\Facades\Storage;

function signingKeyRotationDerivative(Media $media): MediaDerivative
{
    $image = imagecreatetruecolor(2, 2);
    imagefilledrectangle($image, 0, 0, 1, 1, imagecolorallocate($image, 30, 90, 150));
    ob_start();
    imagepng($image);
    $bytes = (string) ob_get_clean();
    imagedestroy($image);
    $path = 'staging/'.$media->id.'/rotation.png';
    Storage::disk('local')->put($path, $bytes);

    return new MediaDerivative([
        'media_id' => $media->id, 'derivative_type' => DerivativeType::PUBLIC,
        'filename' => $path, 'disk' => 'local', 'size' => strlen($bytes),
        'mime_type' => 'image/png', 'checksum' => hash('sha256', $bytes),
    ]);
}

it('does not invalidate watermarks when APP_KEY rotates but WATERMARK_SIGNING_KEY remains unchanged', function () {
    config(['watermark.signing_key' => 'test-signing-key']);
    config(['app.key' => 'test-app-key-1']);

    $media = Media::factory()->create();
    $verificationService = new WatermarkVerificationService(new WatermarkService);
    $payload = $verificationService->generatePayload($media, DerivativeType::PUBLIC);

    $mockWatermarkService = Mockery::mock(WatermarkService::class);
    $mockWatermarkService->shouldReceive('extractInvisibleIdentifier')->andReturn($payload);

    $verificationService = new WatermarkVerificationService($mockWatermarkService);

    $derivative = signingKeyRotationDerivative($media);

    // Initial verification
    $isVerified1 = $verificationService->verifyDerivative($derivative, $media);
    expect($isVerified1)->toBeTrue();

    // Rotate APP_KEY
    config(['app.key' => 'test-app-key-2']);

    // Verify again
    $isVerified2 = $verificationService->verifyDerivative($derivative, $media);
    expect($isVerified2)->toBeTrue();
});

it('invalidates watermarks when WATERMARK_SIGNING_KEY rotates', function () {
    config(['watermark.signing_key' => 'test-signing-key-1']);

    $media = Media::factory()->create();
    $verificationService = new WatermarkVerificationService(new WatermarkService);
    $payload = $verificationService->generatePayload($media, DerivativeType::PUBLIC);

    $mockWatermarkService = Mockery::mock(WatermarkService::class);
    $mockWatermarkService->shouldReceive('extractInvisibleIdentifier')->andReturn($payload);

    $verificationService = new WatermarkVerificationService($mockWatermarkService);

    $derivative = signingKeyRotationDerivative($media);

    // Rotate WATERMARK_SIGNING_KEY
    config(['watermark.signing_key' => 'test-signing-key-2']);

    // Verify again - should fail
    $isVerified = $verificationService->verifyDerivative($derivative, $media);
    expect($isVerified)->toBeFalse();
});
