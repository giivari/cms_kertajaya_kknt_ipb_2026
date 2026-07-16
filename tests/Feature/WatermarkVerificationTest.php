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
        $this->service = new WatermarkVerificationService(new WatermarkService);
        config(['watermark.signing_key' => 'test-signing-key']);
    }

    private function makeDerivative(Media $media): MediaDerivative
    {
        return new MediaDerivative([
            'media_id' => $media->id,
            'derivative_type' => DerivativeType::PUBLIC,
            'filename' => 'test.jpg',
            'disk' => 'public',
            'size' => 100,
            'mime_type' => 'image/jpeg',
        ]);
    }

    private function verifyWithPayload(array $payload, Media $media, MediaDerivative $derivative): bool
    {
        $mock = $this->createMock(WatermarkService::class);
        $mock->method('extractInvisibleIdentifier')->willReturn($payload);
        $svc = new WatermarkVerificationService($mock);

        return $svc->verifyDerivative($derivative, $media);
    }

    public function test_valid_payload_passes_verification()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);

        $this->assertTrue($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_tampered_installation_id_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        $payload['installation_id'] = 'evil-installation';

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_tampered_media_id_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        $payload['media_id'] = 99999;

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_tampered_derivative_type_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        $payload['derivative_type'] = 'private';

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_tampered_watermark_version_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        $payload['watermark_version'] = '9.9';

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_tampered_issued_at_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        $payload['issued_at'] = 0;

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_tampered_signature_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        $payload['signature'] = 'forged-signature-value';

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_missing_signature_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        unset($payload['signature']);

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_missing_required_field_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        unset($payload['watermark_version']);

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_unexpected_extra_field_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);
        $payload = $this->service->generatePayload($media, DerivativeType::PUBLIC);
        $payload['malicious'] = 'injected';

        $this->assertFalse($this->verifyWithPayload($payload, $media, $derivative));
    }

    public function test_missing_signing_key_fails_closed()
    {
        config(['watermark.signing_key' => '']);
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);

        $mock = $this->createMock(WatermarkService::class);
        $mock->method('extractInvisibleIdentifier')->willReturn(['anything' => 'here']);
        $svc = new WatermarkVerificationService($mock);

        $this->assertFalse($svc->verifyDerivative($derivative, $media));
    }

    public function test_null_extraction_fails()
    {
        $media = Media::factory()->create();
        $derivative = $this->makeDerivative($media);

        $mock = $this->createMock(WatermarkService::class);
        $mock->method('extractInvisibleIdentifier')->willReturn(null);
        $svc = new WatermarkVerificationService($mock);

        $this->assertFalse($svc->verifyDerivative($derivative, $media));
    }
}
