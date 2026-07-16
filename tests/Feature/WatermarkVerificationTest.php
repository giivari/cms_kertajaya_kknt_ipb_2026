<?php

namespace Tests\Feature;

use App\Enums\DerivativeType;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatermarkVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected WatermarkVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $watermarkService = new WatermarkService;
        $this->service = new WatermarkVerificationService($watermarkService);
        config(['watermark.signing_key' => 'test-signing-key']);
    }

    public function test_tampering_fails_verification()
    {
        $media = Media::factory()->create();
        $derivative = new MediaDerivative([
            'media_id' => $media->id,
            'derivative_type' => DerivativeType::PUBLIC,
            'filename' => 'test.jpg',
            'disk' => 'public',
            'size' => 100,
            'mime_type' => 'image/jpeg',
        ]);

        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);

        $watermarkServiceMock = $this->createMock(WatermarkService::class);
        $service = new WatermarkVerificationService($watermarkServiceMock);

        // Test missing signature
        $tampered = $payload;
        unset($tampered['signature']);
        $watermarkServiceMock->method('extractInvisibleIdentifier')->willReturn($tampered);
        $this->assertFalse($service->verifyDerivative($derivative, $media));

        // Test modified media_id
        $tampered = $payload;
        $tampered['media_id'] = 'malicious-id';
        $watermarkServiceMock = $this->createMock(WatermarkService::class);
        $watermarkServiceMock->method('extractInvisibleIdentifier')->willReturn($tampered);
        $service = new WatermarkVerificationService($watermarkServiceMock);
        $this->assertFalse($service->verifyDerivative($derivative, $media));

        // Test modified derivative_type
        $tampered = $payload;
        $tampered['derivative_type'] = 'private';
        $watermarkServiceMock = $this->createMock(WatermarkService::class);
        $watermarkServiceMock->method('extractInvisibleIdentifier')->willReturn($tampered);
        $service = new WatermarkVerificationService($watermarkServiceMock);
        $this->assertFalse($service->verifyDerivative($derivative, $media));

        // Test extra field
        $tampered = $payload;
        $tampered['extra'] = 'hacked';
        $watermarkServiceMock = $this->createMock(WatermarkService::class);
        $watermarkServiceMock->method('extractInvisibleIdentifier')->willReturn($tampered);
        $service = new WatermarkVerificationService($watermarkServiceMock);
        $this->assertFalse($service->verifyDerivative($derivative, $media));

        // Test missing key
        config(['watermark.signing_key' => '']);
        $watermarkServiceMock = $this->createMock(WatermarkService::class);
        $watermarkServiceMock->method('extractInvisibleIdentifier')->willReturn($payload);
        $service = new WatermarkVerificationService($watermarkServiceMock);
        $this->assertFalse($service->verifyDerivative($derivative, $media));

        // Test valid payload
        config(['watermark.signing_key' => 'test-signing-key']);
        $watermarkServiceMock = $this->createMock(WatermarkService::class);
        $watermarkServiceMock->method('extractInvisibleIdentifier')->willReturn($payload);
        $service = new WatermarkVerificationService($watermarkServiceMock);
        $this->assertTrue($service->verifyDerivative($derivative, $media));
    }
}
