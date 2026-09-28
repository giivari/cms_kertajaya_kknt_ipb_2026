<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Document;
use App\Jobs\ProcessMediaJob;
use App\Services\DocumentFilePolicy;
use App\Services\WatermarkService;
use App\Services\WatermarkVerificationService;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageComponentRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function createPageWithComponent(string $type, array $data): Page
    {
        $page = Page::create([
            'title' => 'Test '.$type,
            'slug' => 'test-'.str_replace('_', '-', $type),
            'status' => 'published',
            'published_at' => now(),
        ]);
        $section = PageSection::create([
            'page_id' => $page->id,
            'name' => 'Main',
        ]);
        PageComponent::create([
            'section_id' => $section->id,
            'component_type' => $type,
            'content_data' => $data,
            'position' => 0,
        ]);

        return $page;
    }

    public function test_renders_heading()
    {
        $page = $this->createPageWithComponent('heading', ['text' => 'My Heading', 'level' => 'h2']);
        $this->get('/halaman/'.$page->slug)->assertSee('My Heading');
    }

    public function test_renders_rich_text()
    {
        $page = $this->createPageWithComponent('rich_text', ['content' => '<p>Rich Content</p>']);
        $this->get('/halaman/'.$page->slug)->assertSee('Rich Content');
    }

    public function test_renders_image()
    {
        $media = Media::create(['disk' => 'public', 'directory' => 'test', 'filename' => 'test.jpg', 'extension' => 'jpg', 'mime_type' => 'image/jpeg', 'size' => 100, 'original_filename' => 'test.jpg']);
        $page = $this->createPageWithComponent('image', ['media_id' => $media->id]);
        $this->get('/halaman/'.$page->slug)->assertSee('test.jpg'); // Image component doesn't actually render alt text in this implementation if not set, but the view might use original_filename.
    }

    public function test_renders_gallery()
    {
        config(['watermark.signing_key' => 'disposable-page-component-key']);
        $image = imagecreatetruecolor(10, 10);
        imagefilledrectangle($image, 0, 0, 9, 9, imagecolorallocate($image, 50, 120, 90));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        Storage::disk('local')->put('originals/gallery-component.png', $bytes);
        $media = Media::create([
            'disk' => 'local', 'directory' => 'originals', 'filename' => 'gallery-component.png',
            'extension' => 'png', 'mime_type' => 'image/png', 'size' => strlen($bytes),
            'original_filename' => 'GalleryImage', 'checksum' => hash('sha256', $bytes),
            'processing_status' => 'pending', 'invisible_watermark_status' => 'pending',
        ]);
        (new ProcessMediaJob($media))->handle(app(WatermarkService::class), app(WatermarkVerificationService::class));
        $this->assertSame('completed', $media->fresh()->processing_status->value);
        $page = $this->createPageWithComponent('gallery', ['images' => [$media->id]]);
        $this->get('/halaman/'.$page->slug)->assertSee('GalleryImage');
    }

    public function test_renders_statistics()
    {
        $page = $this->createPageWithComponent('statistics', ['items' => [['label' => 'Total Warga', 'value' => '1000']]]);
        $this->get('/halaman/'.$page->slug)->assertSee('Total Warga')->assertSee('1000');
    }

    public function test_renders_video()
    {
        $page = $this->createPageWithComponent('video', ['video_url' => 'https://youtube.com/watch?v=123']);
        $this->get('/halaman/'.$page->slug)->assertSee('youtube.com/embed/123'); // Assuming it embeds
    }

    public function test_renders_map()
    {
        $page = $this->createPageWithComponent('map', ['latitude' => '-6.2088', 'longitude' => '106.8456']);
        $this->get('/halaman/'.$page->slug)->assertSee('-6.2088');
    }

    public function test_renders_documents()
    {
        $bytes = $this->pdfBytes();
        $path = 'originals/component-document.pdf';
        Storage::disk('local')->put($path, $bytes);
        $validated = app(DocumentFilePolicy::class)->inspect(Storage::disk('local')->path($path), 'component-document.pdf');
        $media = Media::create([
            'disk' => 'local', 'directory' => 'originals', 'filename' => 'component-document.pdf',
            'extension' => $validated['extension'], 'mime_type' => $validated['mime'],
            'size' => $validated['size'], 'original_filename' => 'component-document.pdf',
            'checksum' => $validated['checksum'],
            'metadata' => ['document_validation' => ['version' => 1, 'format' => 'pdf']],
            'processing_status' => 'completed', 'invisible_watermark_status' => 'unsupported',
        ]);
        $document = Document::create([
            'title' => 'Dokumen Penting', 'file_media_id' => $media->id,
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        $page = $this->createPageWithComponent('documents', ['document_ids' => [$document->id]]);
        $this->get('/halaman/'.$page->slug)->assertSee('Dokumen Penting')
            ->assertSee(route('documents.download', $document->slug));
    }

    public function test_renders_cta_button()
    {
        $page = $this->createPageWithComponent('cta_button', ['text' => 'Klik Di Sini', 'url' => 'https://example.com']);
        $this->get('/halaman/'.$page->slug)->assertSee('Klik Di Sini')->assertSee('https://example.com');
    }

    public function test_renders_card_grid()
    {
        $page = $this->createPageWithComponent('card_grid', ['cards' => [['title' => 'Card Title', 'description' => 'Card Desc']]]);
        $this->get('/halaman/'.$page->slug)->assertSee('Card Title')->assertSee('Card Desc');
    }

    public function test_renders_contact_block()
    {
        $page = $this->createPageWithComponent('contact_block', ['address' => 'Jalan Desa No 1', 'email' => 'desa@example.com']);
        $this->get('/halaman/'.$page->slug)->assertSee('Jalan Desa No 1')->assertSee('desa@example.com');
    }

    private function pdfBytes(): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
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
