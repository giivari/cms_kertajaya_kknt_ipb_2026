<?php

namespace Tests\Feature;

use App\Http\Controllers\MediaController;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::factory()->create(), 'web');
        Queue::fake();

        // Define route just for testing the Request validation and processing
        Route::middleware('web')->post('/admin/media/upload', [MediaController::class, 'store']);
    }

    public function test_upload_accepts_valid_files()
    {
        Storage::fake('private');
        $image = UploadedFile::fake()->image('test.jpg')->size(100);

        $response = $this->post('/admin/media/upload', [
            'file' => $image,
            'original_filename' => 'Test',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('media', ['original_filename' => 'Test']);
    }

    public function test_upload_rejects_unsupported_mime()
    {
        Storage::fake('private');
        $phpFile = UploadedFile::fake()->create('malicious.php', 10, 'text/x-php');

        $response = $this->post('/admin/media/upload', [
            'file' => $phpFile,
            'original_filename' => 'Hack',
        ]);

        $response->assertInvalid('file');
    }

    public function test_upload_rejects_svg_and_executables()
    {
        Storage::fake('private');
        $svgFile = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');

        $response = $this->post('/admin/media/upload', [
            'file' => $svgFile,
            'original_filename' => 'SVG',
        ]);

        $response->assertInvalid('file');
    }

    public function test_upload_rejects_oversized_files()
    {
        Storage::fake('private');
        $largeFile = UploadedFile::fake()->create('large.jpg', 15000, 'image/jpeg');

        $response = $this->post('/admin/media/upload', [
            'file' => $largeFile,
            'original_filename' => 'Large',
        ]);

        $response->assertInvalid('file');
    }

    public function test_upload_rejects_unsafe_pdf()
    {
        Storage::fake('private');
        $unsafePdfContent = "%PDF-1.4\n/JavaScript /JS /Launch\n%%EOF";
        $tempFile = tempnam(sys_get_temp_dir(), 'unsafe_pdf');
        file_put_contents($tempFile, $unsafePdfContent);

        $file = new UploadedFile($tempFile, 'test.pdf', 'application/pdf', null, true);

        $response = $this->post('/admin/media/upload', [
            'file' => $file,
            'original_filename' => 'Unsafe PDF',
        ]);

        $response->assertInvalid('file');
    }
}
