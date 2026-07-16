<?php

use App\Enums\DerivativeType;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;

it('does not invalidate watermarks when APP_KEY rotates but WATERMARK_SIGNING_KEY remains unchanged', function () {
    config(['watermark.signing_key' => 'test-signing-key']);
    config(['app.key' => 'test-app-key-1']);

    $media = Media::factory()->create();
    $verificationService = new WatermarkVerificationService(new WatermarkService);
    $payload = $verificationService->generatePayload($media, DerivativeType::PUBLIC);

    $mockWatermarkService = Mockery::mock(WatermarkService::class);
    $mockWatermarkService->shouldReceive('extractInvisibleIdentifier')->andReturn($payload);

    $verificationService = new WatermarkVerificationService($mockWatermarkService);

    $derivative = MediaDerivative::factory()->create(['media_id' => $media->id]);

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

    $derivative = MediaDerivative::factory()->create(['media_id' => $media->id]);

    // Rotate WATERMARK_SIGNING_KEY
    config(['watermark.signing_key' => 'test-signing-key-2']);

    // Verify again - should fail
    $isVerified = $verificationService->verifyDerivative($derivative, $media);
    expect($isVerified)->toBeFalse();
});
