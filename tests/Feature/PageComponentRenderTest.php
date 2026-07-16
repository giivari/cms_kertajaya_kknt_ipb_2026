<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $media = Media::create(['disk' => 'public', 'directory' => 'test', 'filename' => 'test2.jpg', 'extension' => 'jpg', 'mime_type' => 'image/jpeg', 'size' => 100, 'original_filename' => 'GalleryImage']);
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
        $media = Media::create(['disk' => 'public', 'directory' => 'test', 'filename' => 'doc.pdf', 'extension' => 'pdf', 'mime_type' => 'application/pdf', 'size' => 100, 'original_filename' => 'Dokumen Penting']);
        $page = $this->createPageWithComponent('documents', ['documents' => [$media->id]]);
        $this->get('/halaman/'.$page->slug)->assertSee('Dokumen Penting');
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
}
