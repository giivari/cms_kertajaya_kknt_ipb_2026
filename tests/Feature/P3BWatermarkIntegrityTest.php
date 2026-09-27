<?php

namespace Tests\Feature;

use App\Enums\DerivativeType;
use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Jobs\ProcessMediaJob;
use App\Models\Admin;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Models\WebsiteSetting;
use App\Services\SettingsService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class P3BWatermarkIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config([
            'watermark.signing_key' => 'p3b-test-signing-key',
            'village.installation_id' => 'P3B-TEST-INSTALLATION',
        ]);
    }

    public function test_verification_requires_authentic_metadata_final_bytes_and_a_decodable_format(): void
    {
        $media = $this->media();
        $derivative = $this->signedDerivative($media);

        $result = app(WatermarkVerificationService::class)->verifyDerivativeResult($derivative, $media);

        $this->assertTrue($result['verified']);
        $this->assertTrue($result['metadata_authentic']);
        $this->assertTrue($result['file_integrity']);
        $this->assertTrue($result['format_valid']);
    }

    public function test_modified_final_bytes_fail_integrity_even_when_the_file_remains_decodable(): void
    {
        $media = $this->media();
        $derivative = $this->signedDerivative($media);
        Storage::disk('local')->append($derivative->filename, 'tampered');

        $result = app(WatermarkVerificationService::class)->verifyDerivativeResult($derivative, $media);

        $this->assertFalse($result['verified']);
        $this->assertTrue($result['format_valid']);
        $this->assertFalse($result['file_integrity']);
        $this->assertSame('checksum_mismatch', $result['reason']);
    }

    public function test_wrong_media_identity_fails_authenticity_even_with_a_valid_signature(): void
    {
        $media = $this->media();
        $otherMedia = $this->media();
        $payload = app(WatermarkVerificationService::class)->generatePayload($otherMedia, DerivativeType::PUBLIC);
        $derivative = $this->signedDerivative($media, $payload);

        $result = app(WatermarkVerificationService::class)->verifyDerivativeResult($derivative, $media);

        $this->assertFalse($result['verified']);
        $this->assertTrue($result['format_valid']);
        $this->assertTrue($result['file_integrity']);
        $this->assertFalse($result['metadata_authentic']);
        $this->assertSame('metadata_invalid', $result['reason']);
    }

    public function test_corrupt_bytes_and_missing_checksum_are_not_verified(): void
    {
        $media = $this->media();
        $path = 'staging/'.$media->id.'/corrupt.png';
        Storage::disk('local')->put($path, 'not an image');
        $derivative = new MediaDerivative([
            'media_id' => $media->id,
            'derivative_type' => DerivativeType::PUBLIC,
            'filename' => $path,
            'disk' => 'local',
            'size' => 12,
            'mime_type' => 'image/png',
            'checksum' => hash('sha256', 'not an image'),
        ]);

        $corrupt = app(WatermarkVerificationService::class)->verifyDerivativeResult($derivative, $media);
        $this->assertFalse($corrupt['verified']);
        $this->assertFalse($corrupt['format_valid']);
        $this->assertSame('format_invalid', $corrupt['reason']);

        $signed = $this->signedDerivative($media);
        $signed->checksum = null;
        $legacy = app(WatermarkVerificationService::class)->verifyDerivativeResult($signed, $media);
        $this->assertFalse($legacy['verified']);
        $this->assertTrue($legacy['legacy_unverifiable']);
        $this->assertSame('checksum_missing', $legacy['reason']);
    }

    public function test_verification_persists_actual_failure_for_the_active_derivative(): void
    {
        $media = $this->media();
        $derivative = $this->signedDerivative($media, persist: true);
        $media->update([
            'processing_status' => MediaProcessingStatus::COMPLETED,
            'invisible_watermark_status' => InvisibleWatermarkStatus::VERIFIED,
        ]);
        Storage::disk('local')->append($derivative->filename, 'tampered');

        $result = app(WatermarkVerificationService::class)
            ->verifyAndPersistActiveDerivative($derivative, $media);

        $this->assertFalse($result['verified']);
        $media->refresh();
        $this->assertSame(InvisibleWatermarkStatus::FAILED, $media->invisible_watermark_status);
        $this->assertSame('verification_failed', $media->last_processing_status);
        $this->assertDatabaseHas('watermark_verification_logs', [
            'media_id' => $media->id,
            'is_verified' => false,
        ]);
    }

    public function test_watermark_logo_setting_resolves_an_approved_media_id_not_a_public_path(): void
    {
        $logo = $this->media();
        $logoDerivative = $this->signedDerivative($logo, persist: true);
        $logoPath = 'derivatives/'.$logo->id.'/logo.png';
        Storage::disk('local')->move($logoDerivative->filename, $logoPath);
        $logoDerivative->update(['filename' => $logoPath]);
        $logo->update([
            'processing_status' => MediaProcessingStatus::COMPLETED,
            'invisible_watermark_status' => InvisibleWatermarkStatus::VERIFIED,
        ]);
        $target = Storage::disk('local')->path('scratch/p3b-target.png');
        Storage::disk('local')->put('scratch/p3b-target.png', $this->pngBytes(20, 120, 90));
        $before = hash_file('sha256', $target);

        SettingsService::setMany([
            'watermark_image' => $logo->id,
            'watermark_position' => 'bottom-right',
            'watermark_scale' => 25,
        ]);
        app(WatermarkService::class)->applyVisibleWatermark($target);

        $this->assertNotSame($before, hash_file('sha256', $target));
        $this->assertSame($logoPath, $logo->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename);

        // Simulate a historical/disconnected setting without bypassing the
        // supported delete guard. The processor must fail safely rather than
        // treating an arbitrary value as a storage path.
        WebsiteSetting::updateOrCreate(['key' => 'watermark_image'], ['value' => 999999999]);
        Cache::forget('settings.watermark_image');
        $this->expectException(\RuntimeException::class);
        app(WatermarkService::class)->applyVisibleWatermark($target);
    }

    public function test_pdf_metadata_injection_is_explicitly_deferred(): void
    {
        $path = Storage::disk('local')->path('scratch/p3b-document.pdf');
        Storage::disk('local')->put('scratch/p3b-document.pdf', "%PDF-1.4\nfixture\n%%EOF\n");

        $this->assertFalse(app(WatermarkService::class)->injectInvisibleIdentifier($path, 'application/pdf', ['media_id' => 1]));
        $this->assertSame("%PDF-1.4\nfixture\n%%EOF\n", file_get_contents($path));
    }

    public function test_manual_verification_action_persists_and_notifies_the_actual_result(): void
    {
        $media = $this->media();
        $derivative = $this->signedDerivative($media, persist: true);
        $activePath = 'derivatives/'.$media->id.'/verified.png';
        Storage::disk('local')->move($derivative->filename, $activePath);
        $derivative->update(['filename' => $activePath]);
        $media->update([
            'processing_status' => MediaProcessingStatus::COMPLETED,
            'invisible_watermark_status' => InvisibleWatermarkStatus::VERIFIED,
        ]);
        $admin = Admin::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListMedia::class)
            ->callTableAction('verify', $media)
            ->assertHasNoTableActionErrors()
            ->assertNotified();
        $this->assertSame(InvisibleWatermarkStatus::VERIFIED, $media->fresh()->invisible_watermark_status);

        Storage::disk('local')->append($activePath, 'tampered');
        Livewire::actingAs($admin)
            ->test(ListMedia::class)
            ->callTableAction('verify', $media->fresh())
            ->assertHasNoTableActionErrors()
            ->assertNotified();
        $this->assertSame(InvisibleWatermarkStatus::FAILED, $media->fresh()->invisible_watermark_status);
    }

    public function test_real_processing_uses_final_checksum_and_keeps_the_active_derivative_on_verification_failure(): void
    {
        $bytes = $this->pngBytes(30, 80, 220);
        $filename = 'original-'.uniqid().'.png';
        Storage::disk('local')->put('originals/'.$filename, $bytes);
        $media = Media::create([
            'disk' => 'local',
            'directory' => 'originals',
            'filename' => $filename,
            'original_filename' => 'fixture.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => strlen($bytes),
            'width' => 10,
            'height' => 10,
            'metadata' => [],
            'checksum' => hash('sha256', $bytes),
            'processing_status' => MediaProcessingStatus::PENDING,
            'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
        ]);

        (new ProcessMediaJob($media))->handle(
            app(WatermarkService::class),
            app(WatermarkVerificationService::class),
        );

        $processed = $media->fresh();
        $active = $processed->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole();
        $this->assertSame(MediaProcessingStatus::COMPLETED, $processed->processing_status);
        $this->assertSame(InvisibleWatermarkStatus::VERIFIED, $processed->invisible_watermark_status);
        $this->assertSame(hash_file('sha256', Storage::disk('local')->path($active->filename)), $active->checksum);
        $this->assertTrue(app(WatermarkVerificationService::class)->verifyDerivative($active, $processed));

        $previousPath = $active->filename;
        SettingsService::set('enable_visible_watermark', true);
        WebsiteSetting::updateOrCreate(['key' => 'watermark_image'], ['value' => 999999999]);
        Cache::forget('settings.watermark_image');

        (new ProcessMediaJob($processed))->handle(
            app(WatermarkService::class),
            app(WatermarkVerificationService::class),
        );

        $afterFailure = $media->fresh();
        $this->assertSame($previousPath, $afterFailure->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename);
        $this->assertSame(MediaProcessingStatus::COMPLETED, $afterFailure->processing_status);
        $this->assertSame(InvisibleWatermarkStatus::VERIFIED, $afterFailure->invisible_watermark_status);
        $this->assertSame('failed', $afterFailure->last_processing_status);
    }

    private function media(): Media
    {
        return Media::create([
            'disk' => 'local',
            'directory' => 'originals',
            'filename' => 'original-'.uniqid().'.png',
            'original_filename' => 'fixture.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => 1,
            'metadata' => [],
            'processing_status' => MediaProcessingStatus::PENDING,
            'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
        ]);
    }

    private function signedDerivative(Media $media, ?array $payload = null, bool $persist = false): MediaDerivative
    {
        $path = 'staging/'.$media->id.'/candidate-'.uniqid().'.png';
        Storage::disk('local')->put($path, $this->pngBytes(220, 30, 50));
        $payload ??= app(WatermarkVerificationService::class)->generatePayload($media, DerivativeType::PUBLIC);
        $this->assertTrue(app(WatermarkService::class)->injectInvisibleIdentifier(
            Storage::disk('local')->path($path),
            'image/png',
            $payload,
        ));
        $bytes = Storage::disk('local')->get($path);
        $attributes = [
            'media_id' => $media->id,
            'derivative_type' => DerivativeType::PUBLIC,
            'filename' => $path,
            'disk' => 'local',
            'size' => strlen($bytes),
            'mime_type' => 'image/png',
            'checksum' => hash('sha256', $bytes),
            'width' => 2,
            'height' => 2,
        ];

        return $persist ? $media->derivatives()->create($attributes) : new MediaDerivative($attributes);
    }

    private function pngBytes(int $red = 220, int $green = 30, int $blue = 50): string
    {
        $image = imagecreatetruecolor(10, 10);
        imagefilledrectangle($image, 0, 0, 9, 9, imagecolorallocate($image, $red, $green, $blue));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
