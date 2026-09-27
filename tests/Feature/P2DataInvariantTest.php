<?php

namespace Tests\Feature;

use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\GalleryAlbums\Pages\ListGalleryAlbums;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Models\Admin;
use App\Models\GalleryAlbum;
use App\Models\GalleryAlbumItem;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use App\Services\CategoryMutationService;
use App\Services\PageBuilderService;
use App\Services\ScopedPositionService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Tests\TestCase;

class P2DataInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_filament_resource_lists_construct(): void
    {
        $this->actingAs(Admin::factory()->create());

        foreach ([ListGalleryAlbums::class, ListDocuments::class, ListLocations::class] as $page) {
            Livewire::test($page)->assertSuccessful();
        }
    }

    public function test_category_delta_preserves_categories_outside_the_editor_snapshot_and_rolls_back_guarded_delete(): void
    {
        $admin = Admin::factory()->create();
        $this->assertTrue(Gate::forUser($admin)->allows('create', NewsCategory::class));
        $a = NewsCategory::create(['name' => 'A']);
        $b = NewsCategory::create(['name' => 'B']);
        $snapshot = [$a->id, $b->id];
        $versions = $this->categoryVersions([$a, $b]);
        $c = NewsCategory::create(['name' => 'C']);

        app(CategoryMutationService::class)->syncNews($admin, [
            ['id' => $a->id, 'name' => 'A revised'],
        ], $snapshot, $versions);

        $this->assertDatabaseHas('news_categories', ['id' => $a->id, 'name' => 'A revised']);
        $this->assertSoftDeleted('news_categories', ['id' => $b->id]);
        $this->assertDatabaseHas('news_categories', ['id' => $c->id, 'name' => 'C']);

        $rollbackCategory = NewsCategory::create(['name' => 'Must roll back']);
        $guarded = NewsCategory::create(['name' => 'Guarded']);
        News::create(['title' => 'Uses guarded category', 'content' => 'x', 'news_category_id' => $guarded->id]);
        $guardedVersion = $this->categoryVersions([$rollbackCategory, $guarded]);

        try {
            app(CategoryMutationService::class)->syncNews($admin, [
                ['id' => $rollbackCategory->id, 'name' => 'Changed before denied delete'],
            ], [$rollbackCategory->id, $guarded->id], $guardedVersion);
            $this->fail('Expected the guarded category delete to be denied.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('news_categories', ['id' => $guarded->id, 'name' => 'Guarded']);
            $this->assertDatabaseHas('news_categories', ['id' => $rollbackCategory->id, 'name' => 'Must roll back']);
        }
    }

    public function test_page_builder_child_failure_rolls_back_parent_and_successful_roundtrip_preserves_identity_and_metadata(): void
    {
        $page = Page::create(['title' => 'Before transaction', 'slug' => 'p2-builder-before']);
        $service = app(PageBuilderService::class);

        try {
            DB::transaction(function () use ($page, $service): void {
                $page->update(['title' => 'Must roll back']);
                $service->saveSectionsAndComponents($page, [[
                    'name' => 'Created before invalid child',
                    'layout_type' => 'two_column',
                    'is_visible' => false,
                    'section_settings' => ['spacing' => 'lg'],
                    'components' => [
                        ['type' => 'heading', 'data' => ['content' => 'Valid first child']],
                        ['type' => 'heading', 'data' => 'invalid child payload'],
                    ],
                ]]);
            });
            $this->fail('Expected the real builder service to reject the invalid child payload.');
        } catch (\InvalidArgumentException) {
            $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'Before transaction']);
            $this->assertDatabaseMissing('page_sections', ['page_id' => $page->id]);
            $this->assertDatabaseMissing('page_components', ['section_id' => 1]);
        }

        $service->saveSectionsAndComponents($page, [[
            'name' => 'Preserved section',
            'layout_type' => 'two_column',
            'is_visible' => false,
            'section_settings' => ['spacing' => 'lg'],
            'components' => [['type' => 'heading', 'data' => ['content' => 'Original']]],
        ]]);
        $section = PageSection::where('page_id', $page->id)->sole();
        $component = PageComponent::where('section_id', $section->id)->sole();
        $section->update(['section_settings' => ['spacing' => 'xl']]);
        $component->update(['component_settings' => ['tone' => 'primary'], 'is_visible' => false]);

        $state = $service->reconstructBuilderState($page->fresh('sections.components'));
        $service->saveSectionsAndComponents($page, array_values($state));

        $this->assertSame($section->id, PageSection::where('page_id', $page->id)->sole()->id);
        $persistedComponent = PageComponent::where('section_id', $section->id)->sole();
        $this->assertSame($component->id, $persistedComponent->id);
        $this->assertSame(['spacing' => 'xl'], $section->fresh()->section_settings);
        $this->assertSame(['tone' => 'primary'], $persistedComponent->component_settings);
        $this->assertFalse($persistedComponent->is_visible);
    }

    public function test_settings_contract_is_deterministic_and_invalidates_array_cache_after_atomic_batch_save(): void
    {
        Cache::flush();
        $this->assertSame('first-default', SettingsService::get('p2_missing_key', 'first-default'));
        $this->assertSame('second-default', SettingsService::get('p2_missing_key', 'second-default'));

        SettingsService::setMany([
            'potensi_3_custom_url' => 'https://example.test/potensi',
            'p2_boolean' => true,
            'p2_structure' => ['label' => 'Preserved type'],
        ]);

        $this->assertSame('https://example.test/potensi', SettingsService::get('potensi_3_custom_url'));
        $this->assertTrue(SettingsService::get('p2_boolean'));
        $this->assertSame(['label' => 'Preserved type'], SettingsService::get('p2_structure'));

        SettingsService::set('p2_boolean', false);
        $this->assertFalse(SettingsService::get('p2_boolean'));
    }

    public function test_publication_scope_and_search_require_published_past_timestamp(): void
    {
        $past = now()->subMinute();
        $future = now()->addMinute();
        $records = [
            ['title' => 'P2 past public', 'slug' => 'p2-past-public', 'status' => 'published', 'published_at' => $past],
            ['title' => 'P2 future hidden', 'slug' => 'p2-future-hidden', 'status' => 'published', 'published_at' => $future],
            ['title' => 'P2 null hidden', 'slug' => 'p2-null-hidden', 'status' => 'published', 'published_at' => null],
            ['title' => 'P2 draft hidden', 'slug' => 'p2-draft-hidden', 'status' => 'draft', 'published_at' => $past],
        ];

        foreach ($records as $record) {
            $page = Page::create(['title' => $record['title'], 'slug' => $record['slug'], 'status' => 'draft']);
            DB::table('pages')->where('id', $page->id)->update(['status' => $record['status'], 'published_at' => $record['published_at']]);
        }

        $this->assertSame(['p2-past-public'], Page::published()->pluck('slug')->all());
        $this->get(route('pages.show', 'p2-past-public'))->assertOk();
        $this->get(route('pages.show', 'p2-future-hidden'))->assertNotFound();
        $this->get(route('pages.show', 'p2-null-hidden'))->assertNotFound();
        $this->get(route('pages.show', 'p2-draft-hidden'))->assertNotFound();
        $this->get(route('public.search', ['q' => 'P2']))
            ->assertSee('P2 past public')
            ->assertDontSee('P2 future hidden')
            ->assertDontSee('P2 null hidden')
            ->assertDontSee('P2 draft hidden');
    }

    public function test_search_handles_malformed_and_literal_wildcard_input(): void
    {
        $page = Page::create(['title' => '100%_literal \\ Unicode Desa', 'slug' => 'p2-literal-search', 'status' => 'published']);
        DB::table('pages')->where('id', $page->id)->update(['published_at' => now()->subMinute()]);

        $this->get('/pencarian?q[]=x')->assertOk()->assertDontSee('100%_literal');
        $this->get(route('public.search', ['q' => '%_']))->assertOk()->assertSee('100%_literal');
        $this->get(route('public.search', ['q' => '\\ Unicode']))->assertOk()->assertSee('100%_literal');
        $this->get(route('public.search', ['q' => 'Desa']))->assertOk()->assertSee('100%_literal');
        $this->get(route('public.search', ['q' => str_repeat('x', 101)]))->assertOk();
    }

    public function test_scoped_position_reservation_allows_gallery_and_menu_sibling_swaps(): void
    {
        $album = GalleryAlbum::create(['title' => 'P2 ordered album']);
        $first = GalleryAlbumItem::create(['gallery_album_id' => $album->id, 'media_id' => Media::factory()->create()->id, 'position' => 0]);
        $second = GalleryAlbumItem::create(['gallery_album_id' => $album->id, 'media_id' => Media::factory()->create()->id, 'position' => 1]);
        $menu = Menu::create(['location' => Menu::HEADER]);
        $parent = MenuItem::create(['menu_id' => $menu->id, 'label' => 'One', 'link_type' => 'custom', 'custom_url' => 'https://example.test/one', 'position' => 0]);
        $peer = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Two', 'link_type' => 'custom', 'custom_url' => 'https://example.test/two', 'position' => 1]);
        $childOne = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $parent->id, 'label' => 'Child one', 'link_type' => 'custom', 'custom_url' => 'https://example.test/child-one', 'position' => 0]);
        $childTwo = MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $parent->id, 'label' => 'Child two', 'link_type' => 'custom', 'custom_url' => 'https://example.test/child-two', 'position' => 1]);

        DB::transaction(function () use ($album, $first, $second, $menu, $parent, $peer, $childOne, $childTwo): void {
            $service = app(ScopedPositionService::class);
            $service->reserveGalleryAlbumItems($album);
            $first->update(['position' => 1]);
            $second->update(['position' => 0]);
            $service->reserveMenuItemPositions($menu);
            $parent->update(['position' => 1]);
            $peer->update(['position' => 0]);
            $childOne->update(['position' => 1]);
            $childTwo->update(['position' => 0]);
        });

        $this->assertSame([0, 1], $album->items()->pluck('position')->all());
        $this->assertSame([0, 1], $menu->allItems()->whereNull('parent_id')->orderBy('position')->pluck('position')->all());
        $this->assertSame([0, 1], $parent->children()->pluck('position')->all());

        try {
            DB::transaction(function () use ($album, $first): void {
                app(ScopedPositionService::class)->reserveGalleryAlbumItems($album);
                $first->update(['position' => 99]);
                throw new \RuntimeException('Controlled reorder failure');
            });
        } catch (\RuntimeException) {
            $this->assertSame(1, $first->fresh()->position);
            $this->assertSame(0, $second->fresh()->position);
        }
    }

    /** @param array<int, NewsCategory> $categories */
    private function categoryVersions(array $categories): array
    {
        return collect($categories)->mapWithKeys(fn (NewsCategory $category): array => [
            (string) $category->id => $category->fresh()->updated_at->format('Y-m-d\\TH:i:s.uP'),
        ])->all();
    }
}
