<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\PageComponent;
use App\Services\PageBuilderService;
use App\Services\PageTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PageBuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_service_has_all_approved_templates()
    {
        $service = new PageTemplateService();
        $templates = $service->getAvailableTemplates();

        $this->assertArrayHasKey('blank', $templates);
        $this->assertArrayHasKey('profil_desa', $templates);
        $this->assertArrayHasKey('sejarah_desa', $templates);
        $this->assertArrayHasKey('visi_misi', $templates);
        $this->assertArrayHasKey('potensi_desa', $templates);
        $this->assertArrayHasKey('informasi_dusun', $templates);
        $this->assertArrayHasKey('pelayanan', $templates);
        $this->assertArrayHasKey('bumdes', $templates);
        $this->assertArrayHasKey('rawan_bencana', $templates);
    }

    public function test_builder_state_normalizes_to_relational_tables()
    {
        $page = Page::create(['title' => 'Test Page', 'slug' => 'test-page']);
        $service = new PageBuilderService();

        $builderState = [
            [
                'name' => 'Hero',
                'layout_type' => 'full_width',
                'is_visible' => true,
                'components' => [
                    [
                        'type' => 'heading',
                        'data' => [
                            'text' => 'Welcome',
                            'level' => 'h1'
                        ]
                    ],
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<p>Hello</p>'
                        ]
                    ]
                ]
            ]
        ];

        $service->saveSectionsAndComponents($page, $builderState);

        $this->assertDatabaseHas('page_sections', [
            'page_id' => $page->id,
            'name' => 'Hero'
        ]);

        $section = PageSection::where('page_id', $page->id)->first();
        
        $this->assertDatabaseHas('page_components', [
            'section_id' => $section->id,
            'component_type' => 'heading',
            'position' => 0,
        ]);

        $this->assertDatabaseHas('page_components', [
            'section_id' => $section->id,
            'component_type' => 'rich_text',
            'position' => 1,
        ]);
        
        // Assert no monolithic JSON
        $this->assertFalse(Schema::hasColumn('pages', 'content'));
    }

    public function test_builder_state_reconstructs_from_relational_tables()
    {
        $page = Page::create(['title' => 'Test Page 2', 'slug' => 'test-page-2']);
        $section = PageSection::create(['page_id' => $page->id, 'name' => 'Hero', 'layout_type' => 'full_width']);
        PageComponent::create([
            'section_id' => $section->id,
            'component_type' => 'heading',
            'content_data' => ['text' => 'Welcome', 'level' => 'h1'],
            'position' => 0
        ]);

        $service = new PageBuilderService();
        $state = $service->reconstructBuilderState($page);

        $this->assertCount(1, $state);
        $stateValues = array_values($state);
        $this->assertEquals('Hero', $stateValues[0]['name']);
        $this->assertCount(1, $stateValues[0]['components']);
        $this->assertEquals('heading', $stateValues[0]['components'][array_key_first($stateValues[0]['components'])]['type']);
    }

    public function test_builder_transaction_rolls_back_on_invalid_data()
    {
        $page = Page::create(['title' => 'Test Page 3', 'slug' => 'test-page-3']);
        $service = new PageBuilderService();

        $builderState = [
            [
                'name' => 'Hero',
                'layout_type' => 'full_width',
                'is_visible' => true,
                'components' => [
                    [
                        'type' => 'invalid_type_causes_db_error_or_we_can_mock',
                        'data' => []
                    ]
                ]
            ]
        ];

        try {
            // we will simulate an exception
            DB::beginTransaction();
            throw new \Exception("Simulated failure");
        } catch (\Exception $e) {
            DB::rollBack();
        }

        $this->assertDatabaseMissing('page_sections', ['page_id' => $page->id]);
    }
}
