<?php

namespace Tests\Feature;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use App\Services\PageTemplateService;
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
            'section_id' => $section->id,
            'component_type' => 'heading',
            'content_data' => ['text' => 'Hello World', 'level' => 'h2'],
            'position' => 0,
        ]);

        $response = $this->get('/halaman/published-page');
        $response->assertStatus(200);
        $response->assertSee('Published Page');
        $response->assertSee('Hello World');
    }

    public function test_template_service_returns_definitions()
    {
        $service = new PageTemplateService;
        $templates = $service->getAvailableTemplates();

        $this->assertArrayHasKey('profil_desa', $templates);

        $definition = $service->getTemplateDefinition('profil_desa');
        $this->assertIsArray($definition);
        $this->assertCount(2, $definition);
        $this->assertEquals('heading', $definition[0]['components'][array_key_first($definition[0]['components'])]['type']);
    }
}
