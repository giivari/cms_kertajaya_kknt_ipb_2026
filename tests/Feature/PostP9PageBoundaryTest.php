<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use App\Services\PageBuilderService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** The editor actions must manage their own transaction; this test supplies none. */
class PostP9PageBoundaryTest extends TestCase
{
    private ?string $adminId = null;

    /** @var array<int, int> */
    private array $pageIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->adminId = $admin->id;
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        // These fixtures live only in the guardrail-approved disposable DB.
        // Cleanup is outside the operation under test, so it cannot supply the
        // transaction whose absence caused F22.
        foreach ($this->pageIds as $id) {
            DB::table('page_components')->whereIn('section_id', DB::table('page_sections')->select('id')->where('page_id', $id))->delete();
            DB::table('page_sections')->where('page_id', $id)->delete();
            DB::table('pages')->where('id', $id)->delete();
        }
        if ($this->adminId !== null) {
            DB::table('audit_logs')->where('admin_id', $this->adminId)->delete();
            DB::table('admins')->where('id', $this->adminId)->delete();
        }
        parent::tearDown();
    }

    private function state(string $name, string $text): array
    {
        return [[
            'name' => $name,
            'layout_type' => 'two_columns',
            'is_visible' => false,
            'components' => [['type' => 'heading', 'data' => [
                'text' => $text, 'level' => 'h2', 'alignment' => 'left',
            ]]],
        ]];
    }

    private function failAfterRealComponentWrite(): void
    {
        $this->app->instance(PageBuilderService::class, new class extends PageBuilderService
        {
            protected function saveComponents(PageSection $section, array $componentsData): void
            {
                parent::saveComponents($section, $componentsData);
                throw new \RuntimeException('Controlled failure after component persistence.');
            }
        });
    }

    public function test_actual_create_rolls_back_parent_children_and_success_audit(): void
    {
        $this->failAfterRealComponentWrite();

        try {
            Livewire::test(CreatePage::class)->fillForm([
                'title' => 'Post P9 failed create',
                'builder_sections' => $this->state('Rollback section', 'Rollback component'),
            ])->call('create');
            $this->fail('Expected the actual builder write to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Controlled failure after component persistence.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('pages', ['title' => 'Post P9 failed create']);
        $this->assertDatabaseMissing('page_sections', ['name' => 'Rollback section']);
        $this->assertDatabaseMissing('page_components', ['component_type' => 'heading']);
        $this->assertSame(0, AuditLog::where('event_type', 'page_created')->count());
    }

    public function test_actual_edit_rolls_back_parent_and_existing_tree_then_success_preserves_ids(): void
    {
        $page = Page::create(['title' => 'Post P9 original', 'slug' => 'post-p9-page']);
        $this->pageIds[] = $page->id;
        app(PageBuilderService::class)->saveSectionsAndComponents($page, $this->state('Original section', 'Original component'));
        $section = PageSection::where('page_id', $page->id)->sole();
        $component = PageComponent::where('section_id', $section->id)->sole();
        $section->update(['section_settings' => ['spacing' => 'xl']]);
        $component->update(['component_settings' => ['tone' => 'primary'], 'is_visible' => false]);

        $this->failAfterRealComponentWrite();
        $editor = Livewire::test(EditPage::class, ['record' => $page->id]);
        $state = $editor->get('data.builder_sections');
        $key = array_key_first($state);
        $state[$key]['name'] = 'Changed section';
        $state[$key]['components'][] = ['type' => 'heading', 'data' => ['text' => 'New component', 'level' => 'h2']];
        try {
            $editor->fillForm(['title' => 'Post P9 changed', 'builder_sections' => $state])->call('save');
            $this->fail('Expected the actual builder write to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Controlled failure after component persistence.', $exception->getMessage());
        }

        $this->assertSame('Post P9 original', $page->fresh()->title);
        $this->assertSame('Original section', $section->fresh()->name);
        $this->assertSame($component->id, PageComponent::where('section_id', $section->id)->sole()->id);
        $this->assertSame(['spacing' => 'xl'], $section->fresh()->section_settings);
        $this->assertSame(['tone' => 'primary'], $component->fresh()->component_settings);
        $this->assertSame(0, AuditLog::where('event_type', 'page_updated')->count());
    }

    public function test_actual_create_and_edit_roundtrip_preserves_builder_identity(): void
    {
        Livewire::test(CreatePage::class)->fillForm([
            'title' => 'Post P9 successful create',
            'builder_sections' => $this->state('Stable section', 'First component'),
        ])->call('create')->assertHasNoFormErrors();

        $page = Page::where('title', 'Post P9 successful create')->sole();
        $this->pageIds[] = $page->id;
        $section = PageSection::where('page_id', $page->id)->sole();
        $component = PageComponent::where('section_id', $section->id)->sole();
        $section->update(['section_settings' => ['spacing' => 'xl']]);
        $component->update(['component_settings' => ['tone' => 'primary'], 'is_visible' => false]);

        $editor = Livewire::test(EditPage::class, ['record' => $page->id]);
        $state = $editor->get('data.builder_sections');
        $key = array_key_first($state);
        $state[$key]['name'] = 'Stable section changed';
        $editor->fillForm(['title' => 'Post P9 successful edit', 'builder_sections' => $state])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('Post P9 successful edit', $page->fresh()->title);
        $this->assertSame($section->id, PageSection::where('page_id', $page->id)->sole()->id);
        $this->assertSame($component->id, PageComponent::where('section_id', $section->id)->sole()->id);
        $this->assertSame('Stable section changed', $section->fresh()->name);
        $this->assertSame(['spacing' => 'xl'], $section->fresh()->section_settings);
        $this->assertSame(['tone' => 'primary'], $component->fresh()->component_settings);
        $this->assertFalse($component->fresh()->is_visible);
        $this->assertSame(1, AuditLog::where('event_type', 'page_created')->where('subject_id', $page->id)->count());
        $this->assertSame(1, AuditLog::where('event_type', 'page_updated')->where('subject_id', $page->id)->count());
    }
}
