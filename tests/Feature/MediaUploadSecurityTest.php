<?php

namespace Tests\Feature;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Models\Admin;
use App\Models\Media;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Queue::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]));
    }

    public function test_real_media_form_accepts_valid_image_into_private_original_storage(): void
    {
        Livewire::test(CreateMedia::class)->fillForm([
            'original_filename' => 'Test image',
            'file' => UploadedFile::fake()->image('test.jpg', 32, 32),
        ])->call('create')->assertHasNoFormErrors();

        $media = Media::query()->sole();
        Storage::disk('local')->assertExists('originals/'.$media->filename);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_real_media_form_rejects_executable_and_svg_uploads(): void
    {
        foreach ([
            UploadedFile::fake()->create('malicious.php', 10, 'text/x-php'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg onload="alert(1)"/>'),
        ] as $file) {
            Livewire::test(CreateMedia::class)->fillForm([
                'original_filename' => 'Blocked file', 'file' => $file,
            ])->call('create')->assertHasFormErrors(['file']);
        }

        $this->assertSame(0, Media::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_real_media_form_rejects_oversized_image(): void
    {
        Livewire::test(CreateMedia::class)->fillForm([
            'original_filename' => 'Too wide',
            'file' => UploadedFile::fake()->image('wide.jpg', 3841, 10),
        ])->call('create')->assertHasFormErrors(['file']);

        $this->assertSame(0, Media::query()->count());
    }

    public function test_real_media_form_rejects_pdf_document_payload(): void
    {
        Livewire::test(CreateMedia::class)->fillForm([
            'original_filename' => 'Document',
            'file' => UploadedFile::fake()->createWithContent(
                'document.pdf', "%PDF-1.4\n/JavaScript /JS /Launch\n%%EOF\n",
            ),
        ])->call('create')->assertHasFormErrors(['file']);

        $this->assertSame(0, Media::query()->count());
    }
}
