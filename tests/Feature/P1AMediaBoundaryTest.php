<?php

namespace Tests\Feature;

use App\Enums\MediaProcessingStatus;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Jobs\ProcessMediaJob;
use App\Models\Admin;
use App\Models\Media;
use App\Services\MediaInputPolicy;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class P1AMediaBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        Cache::put('settings.max_upload_size', 10);
        Cache::put('settings.max_image_width', 3840);
        Cache::put('settings.max_image_height', 2160);
        Cache::put('settings.enable_visible_watermark', false);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]));
    }

    public function test_real_media_create_form_rejects_oversized_dimensions(): void
    {
        Livewire::test(CreateMedia::class)->fillForm([
            'original_filename' => 'Too wide',
            'file' => UploadedFile::fake()->image('wide.png', 3841, 10),
        ])->call('create')->assertHasFormErrors(['file']);
        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_valid_image_upload_still_reaches_verified_public_output(): void
    {
        $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('injectInvisibleIdentifier')->twice()->andReturn(true);
        });
        $this->mock(WatermarkVerificationService::class, function ($mock) {
            $mock->shouldReceive('generatePayload')->twice()->andReturn([]);
            $mock->shouldReceive('verifyDerivative')->twice()->andReturn(true);
        });
        Livewire::test(CreateMedia::class)->fillForm([
            'original_filename' => 'Valid image',
            'file' => UploadedFile::fake()->image('valid.png', 32, 32),
        ])->call('create')->assertHasNoFormErrors();
        $media = Media::sole();
        $this->assertSame(MediaProcessingStatus::COMPLETED, $media->processing_status);
        $this->assertSame(2, $media->derivatives()->count());
        Storage::disk('local')->assertExists($media->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->sole()->filename);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_plain_pdf_remains_accepted_by_input_and_publication_checks(): void
    {
        $file = UploadedFile::fake()->createWithContent('plain.pdf', $this->pdfBytes());
        $policy = app(MediaInputPolicy::class);
        $this->assertSame('application/pdf', $policy->inspect($file->getRealPath(), 'plain.pdf'));
        $this->assertSame('application/pdf', $policy->inspect($file->getRealPath(), publication: true));
    }

    public function test_real_media_create_form_rejects_spoofed_or_corrupt_image(): void
    {
        Livewire::test(CreateMedia::class)->fillForm([
            'original_filename' => 'Corrupt image',
            'file' => UploadedFile::fake()->createWithContent('broken.png', '<script>alert(1)</script>'),
        ])->call('create')->assertHasFormErrors(['file']);
        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_replace_and_crop_use_the_same_active_form_validation(): void
    {
        $media = $this->imageMedia();
        Livewire::test(EditMedia::class, ['record' => $media->getRouteKey()])->fillForm([
            'file' => UploadedFile::fake()->image('crop.png', 3841, 10),
        ])->call('save')->assertHasFormErrors(['file']);
        $this->assertSame('original.png', $media->fresh()->filename);
        Storage::disk('local')->assertExists('originals/original.png');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_active_pdf_is_rejected_by_the_real_upload_form(): void
    {
        Livewire::test(CreateMedia::class)->fillForm([
            'original_filename' => 'Active PDF',
            'file' => UploadedFile::fake()->createWithContent('active.pdf', $this->pdfBytes(active: true)),
        ])->call('create')->assertHasFormErrors(['file']);
        $this->assertSame(0, Media::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_unsupported_processing_never_writes_public_even_with_old_image_metadata(): void
    {
        $media = $this->imageMedia();
        // The stored record still has old image metadata, but its actual bytes
        // are a valid PDF. Processing must use the real bytes, not that metadata.
        Storage::disk('local')->delete('originals/'.$media->filename);
        Storage::disk('local')->put('originals/original.pdf', $this->pdfBytes());
        $media->update(['filename' => 'original.pdf']);
        (new ProcessMediaJob($media))->handle(
            $this->mock(WatermarkService::class),
            $this->mock(WatermarkVerificationService::class),
        );
        $this->assertSame(MediaProcessingStatus::FAILED, $media->fresh()->processing_status);
        $this->assertSame('unsupported', $media->fresh()->invisible_watermark_status->value);
        $this->assertSame(0, $media->derivatives()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failed_watermark_verification_keeps_file_private(): void
    {
        $media = $this->imageMedia();
        $watermark = $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('injectInvisibleIdentifier')->once()->andReturn(true);
        });
        $verification = $this->mock(WatermarkVerificationService::class, function ($mock) {
            $mock->shouldReceive('generatePayload')->once()->andReturn([]);
            $mock->shouldReceive('verifyDerivative')->once()->andReturn(false);
        });
        (new ProcessMediaJob($media))->handle($watermark, $verification);
        $this->assertSame(MediaProcessingStatus::FAILED, $media->fresh()->processing_status);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, $media->derivatives()->count());
    }

    public function test_corrupt_processing_output_is_rejected_before_verifier_or_public_write(): void
    {
        $media = $this->imageMedia();
        $watermark = $this->mock(WatermarkService::class, function ($mock) {
            $mock->shouldReceive('injectInvisibleIdentifier')->once()->andReturnUsing(function ($path) {
                file_put_contents($path, 'not an image');
                return true;
            });
        });
        $verification = $this->mock(WatermarkVerificationService::class, function ($mock) {
            $mock->shouldReceive('generatePayload')->once()->andReturn([]);
            $mock->shouldNotReceive('verifyDerivative');
        });
        (new ProcessMediaJob($media))->handle($watermark, $verification);
        $this->assertSame(MediaProcessingStatus::FAILED, $media->fresh()->processing_status);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, $media->derivatives()->count());
    }

    private function imageMedia(): Media
    {
        $file = UploadedFile::fake()->image('original.png', 32, 32);
        Storage::disk('local')->put('originals/original.png', file_get_contents($file->getRealPath()));
        return Media::factory()->create([
            'filename' => 'original.png', 'directory' => 'originals', 'disk' => 'local',
            'mime_type' => 'image/png', 'extension' => 'png', 'size' => $file->getSize(),
            'processing_status' => 'pending', 'invisible_watermark_status' => 'pending',
        ]);
    }

    private function pdfBytes(bool $active = false): string
    {
        $catalog = '<< /Type /Catalog /Pages 2 0 R';
        if ($active) {
            $catalog .= ' /OpenAction << /S /JavaScript /JS (app.alert(1)) >>';
        }
        $objects = [
            $catalog.' >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Contents 4 0 R >>',
            "<< /Length 0 >>\nstream\n\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1)." 0 obj\n".$body."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }

        return $pdf."trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
