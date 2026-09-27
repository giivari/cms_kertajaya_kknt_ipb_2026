<?php

namespace Tests\Feature;

use App\Enums\DerivativeType;
use App\Filament\Pages\Auth\EditProfile;
use App\Jobs\ProcessMediaJob;
use App\Models\Admin;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Services\MediaDeletionService;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class P3AMediaLifecycleVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_controlled_route_serves_only_the_current_eligible_derivative(): void
    {
        [$media, $bytes] = $this->activeMedia();

        $response = $this->get(route('media.derivative', $media))->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; sandbox");
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertRevocableCacheControl((string) $response->headers->get('Cache-Control'));
        $this->assertSame('max-age=0, must-revalidate, no-store, private', $response->headers->get('Cache-Control'));

        $derivative = $media->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole();
        $derivative->update(['checksum' => str_repeat('0', 64)]);
        $this->get(route('media.derivative', $media))->assertNotFound();

        $derivative->update(['checksum' => hash('sha256', $bytes), 'mime_type' => 'image/jpeg']);
        $this->get(route('media.derivative', $media))->assertNotFound();

        $derivative->update(['mime_type' => 'image/png', 'filename' => '../originals/'.$media->filename]);
        $this->get(route('media.derivative', $media))->assertNotFound();

        $derivative->update(['filename' => 'originals/'.$media->filename]);
        $this->get(route('media.derivative', $media))->assertNotFound();
    }

    public function test_missing_or_unapproved_or_archived_media_is_not_delivered(): void
    {
        [$media] = $this->activeMedia();
        $derivative = $media->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole();

        Storage::disk('local')->delete($derivative->filename);
        $this->get(route('media.derivative', $media))->assertNotFound();

        Storage::disk('local')->put($derivative->filename, $this->pngBytes());
        $media->update(['invisible_watermark_status' => 'failed']);
        $this->get(route('media.derivative', $media))->assertNotFound();

        $media->update(['invisible_watermark_status' => 'verified']);
        $media->delete();
        $this->get(route('media.derivative', $media->id))->assertNotFound();
    }

    public function test_schema_guarantees_one_public_derivative_per_media(): void
    {
        [$media, $bytes] = $this->activeMedia();
        $this->expectException(QueryException::class);
        $media->derivatives()->create([
            'derivative_type' => DerivativeType::PUBLIC,
            'filename' => 'derivatives/'.$media->id.'/second.png',
            'disk' => 'local', 'size' => strlen($bytes), 'mime_type' => 'image/png',
            'checksum' => hash('sha256', $bytes),
        ]);
    }

    public function test_original_route_enforces_the_real_admin_security_boundary(): void
    {
        [$media, $bytes] = $this->activeMedia();
        $url = route('admin.media.original', $media);

        $this->get($url)->assertRedirect();

        $admin = Admin::factory()->create();
        $this->actingAs($admin)->get($url)
            ->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());

        $admin->update(['app_authentication_secret' => AppAuthentication::make()->generateSecret()]);
        $response = $this->actingAs($admin)->get($url)->assertOk();
        $this->assertRevocableCacheControl((string) $response->headers->get('Cache-Control'), private: true);
        $this->assertSame('max-age=0, no-store, private', $response->headers->get('Cache-Control'));
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));

        $admin->update(['force_password_change' => true]);
        $this->actingAs($admin->fresh())->get($url)->assertRedirect(EditProfile::getUrl());

        Storage::disk('local')->delete('originals/'.$media->filename);
        $admin->update(['force_password_change' => false]);
        $this->actingAs($admin->fresh())->get($url)->assertNotFound();
    }

    public function test_archive_restore_and_permanent_delete_preserve_then_revoke_owned_bytes(): void
    {
        [$media] = $this->activeMedia();
        $derivative = $media->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole();
        Storage::disk('local')->put('derivatives/unrelated/keep.png', $this->pngBytes());
        $service = app(MediaDeletionService::class);

        $this->assertTrue($service->archive($media));
        $this->get(route('media.derivative', $media->id))->assertNotFound();
        Storage::disk('local')->assertExists('originals/'.$media->filename);
        Storage::disk('local')->assertExists($derivative->filename);

        $this->assertTrue($service->restore($media->fresh()));
        $this->get(route('media.derivative', $media->id))->assertOk();

        $this->assertTrue($service->archive($media->fresh()));
        $this->assertTrue($service->permanentlyDelete($media->fresh()));
        $this->assertNull(Media::withTrashed()->find($media->id));
        Storage::disk('local')->assertMissing('originals/'.$media->filename);
        Storage::disk('local')->assertMissing($derivative->filename);
        Storage::disk('local')->assertExists('derivatives/unrelated/keep.png');
        $this->get(route('media.derivative', $media->id))->assertNotFound();
    }

    public function test_cleanup_failure_remains_archived_and_can_be_retried_safely(): void
    {
        [$media, $bytes] = $this->activeMedia();
        $service = app(MediaDeletionService::class);
        $service->archive($media);
        $media->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->update(['filename' => 'unsafe/outside.png']);

        try {
            $service->permanentlyDelete($media->fresh());
            $this->fail('Unsafe tracked path must stop permanent deletion.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('cleanup failed', $exception->getMessage());
        }
        $failed = Media::withTrashed()->findOrFail($media->id);
        $this->assertTrue($failed->trashed());
        $this->assertSame('failed', $failed->cleanup_status);

        $safePath = 'derivatives/'.$media->id.'/retry.png';
        Storage::disk('local')->put($safePath, $bytes);
        $failed->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->update(['filename' => $safePath]);
        $this->assertTrue($service->permanentlyDelete($failed));
        $this->assertNull(Media::withTrashed()->find($media->id));
    }

    public function test_reprocess_keeps_original_and_failed_replacement_keeps_active_generation(): void
    {
        $bytes = $this->pngBytes();
        $media = $this->mediaWithOriginal($bytes);
        $originalChecksum = hash('sha256', $bytes);
        $watermark = $this->mock(WatermarkService::class, function ($mock): void {
            $mock->shouldReceive('injectInvisibleIdentifier')->times(5)->andReturnTrue();
        });
        $verification = $this->mock(WatermarkVerificationService::class, function ($mock): void {
            $mock->shouldReceive('generatePayload')->times(5)->andReturn([]);
            $mock->shouldReceive('verifyDerivative')->times(5)->andReturn(true, true, true, true, false);
        });

        (new ProcessMediaJob($media))->handle($watermark, $verification);
        $first = $media->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename;
        (new ProcessMediaJob($media->fresh()))->handle($watermark, $verification);
        $second = $media->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename;
        $this->assertNotSame($first, $second);
        $this->assertSame($originalChecksum, hash('sha256', Storage::disk('local')->get('originals/'.$media->filename)));
        Storage::disk('local')->assertExists($first);
        Storage::disk('local')->assertExists($second);

        (new ProcessMediaJob($media->fresh()))->handle($watermark, $verification);
        $this->assertSame($second, $media->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename);
        $this->get(route('media.derivative', $media->id))->assertOk();
        $this->assertSame($originalChecksum, hash('sha256', Storage::disk('local')->get('originals/'.$media->filename)));
    }

    public function test_stale_job_and_archived_job_cannot_activate_a_generation(): void
    {
        [$media] = $this->activeMedia();
        $activePath = $media->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename;
        $stale = new ProcessMediaJob($media);
        $media->update(['processing_token' => (string) \Illuminate\Support\Str::uuid()]);

        $watermark = $this->mock(WatermarkService::class);
        $verification = $this->mock(WatermarkVerificationService::class);
        $stale->handle($watermark, $verification);
        $this->assertSame($activePath, $media->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename);

        $archivedJob = new ProcessMediaJob($media->fresh());
        app(MediaDeletionService::class)->archive($media->fresh());
        $archivedJob->handle($watermark, $verification);
        $this->assertSame($activePath, Media::withTrashed()->findOrFail($media->id)->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename);
    }

    public function test_activation_rollback_leaves_private_orphan_visible_to_read_only_inventory(): void
    {
        [$media] = $this->activeMedia();
        $activePath = $media->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename;
        $watermark = $this->mock(WatermarkService::class, fn ($mock) => $mock->shouldReceive('injectInvisibleIdentifier')->twice()->andReturnTrue());
        $verification = $this->mock(WatermarkVerificationService::class, function ($mock): void {
            $mock->shouldReceive('generatePayload')->twice()->andReturn([]);
            $mock->shouldReceive('verifyDerivative')->twice()->andReturnTrue();
        });
        Event::listen('eloquent.updating: '.MediaDerivative::class, function (): never {
            throw new \RuntimeException('p3a-activation-failure');
        });

        (new ProcessMediaJob($media->fresh()))->handle($watermark, $verification);
        $this->assertSame($activePath, $media->fresh()->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename);
        $this->assertCount(3, Storage::disk('local')->allFiles('derivatives/'.$media->id));

        Artisan::call('media:lifecycle-inventory', ['--json' => true]);
        $inventory = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(2, $inventory['private_unclassified_generations']);
        Storage::disk('local')->assertExists($activePath);
    }

    /** @return array{Media, string} */
    private function activeMedia(): array
    {
        $bytes = $this->pngBytes();
        $media = $this->mediaWithOriginal($bytes);
        $path = 'derivatives/'.$media->id.'/active.png';
        Storage::disk('local')->put($path, $bytes);
        $media->derivatives()->create([
            'derivative_type' => DerivativeType::PUBLIC,
            'filename' => $path,
            'disk' => 'local', 'size' => strlen($bytes), 'mime_type' => 'image/png',
            'checksum' => hash('sha256', $bytes), 'width' => 2, 'height' => 2,
        ]);
        $media->update(['processing_status' => 'completed', 'invisible_watermark_status' => 'verified']);

        return [$media->fresh()->load('derivatives'), $bytes];
    }

    private function mediaWithOriginal(string $bytes): Media
    {
        $filename = 'original-'.\Illuminate\Support\Str::uuid().'.png';
        Storage::disk('local')->put('originals/'.$filename, $bytes);

        return Media::create([
            'disk' => 'local', 'directory' => 'originals', 'filename' => $filename,
            'original_filename' => 'fixture.png', 'mime_type' => 'image/png', 'extension' => 'png',
            'size' => strlen($bytes), 'width' => 2, 'height' => 2, 'metadata' => [],
            'checksum' => hash('sha256', $bytes), 'processing_status' => 'pending',
            'invisible_watermark_status' => 'pending',
        ]);
    }

    private function pngBytes(): string
    {
        $image = imagecreatetruecolor(2, 2);
        imagefilledrectangle($image, 0, 0, 1, 1, imagecolorallocate($image, 25, 120, 90));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    private function assertRevocableCacheControl(string $value, bool $private = false): void
    {
        $value = strtolower($value);

        $this->assertStringContainsString('no-store', $value);
        $this->assertStringNotContainsString('public', $value);
        if ($private) {
            $this->assertStringContainsString('private', $value);
        }
    }
}
