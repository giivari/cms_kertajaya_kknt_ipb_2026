<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_draft_pages()
    {
        $page = Page::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'status' => PageStatus::DRAFT->value,
        ]);

        $response = $this->get('/halaman/draft-page');
        $response->assertStatus(404);
    }

    public function test_guests_can_view_published_pages()
    {
        $page = Page::create([
            'title' => 'Published Page',
            'slug' => 'published-page',
            'status' => PageStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);

        $section = PageSection::create([
            'page_id' => $page->id,
            'name' => 'Main',
        ]);

        PageComponent::create([
            'page_section_id' => $section->id,
            'component_type' => ComponentType::HEADING->value,
            'content_data' => ['text' => 'Hello World', 'level' => 'h2'],
        ]);

        $response = $this->get('/halaman/published-page');
        $response->assertStatus(200);
        $response->assertSee('Published Page');
        $response->assertSee('Hello World');
    }

    public function test_templates_create_draft_pages()
    {
        $this->seed(\Database\Seeders\PageTemplateSeeder::class);

        $templates = [
            'Profil Desa',
            'Sejarah Desa',
            'Visi dan Misi',
            'Potensi Desa',
            'Informasi Dusun',
            'Pelayanan',
            'BUMDes',
            'Rawan Bencana',
        ];

        foreach ($templates as $template) {
            $page = Page::where('title', $template)->first();
            $this->assertNotNull($page);
            $this->assertEquals(PageStatus::DRAFT, $page->status);
            $this->assertGreaterThan(0, $page->sections->count());
        }
    }
}
