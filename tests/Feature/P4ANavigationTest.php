<?php

namespace Tests\Feature;

use App\Enums\LinkType;
use App\Filament\Providers\GlobalSearchProvider;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\EditFooterMenu;
use App\Models\Admin;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\NavigationResolver;
use App\Support\Preview\PreviewContext;
use App\Filament\Support\PreviewStateNormalizer;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class P4ANavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_public_reads_are_read_only_and_header_footer_use_separate_menus(): void
    {
        $this->get('/')->assertOk();
        $this->assertSame(0, Menu::count());
        $this->assertSame(0, MenuItem::count());

        $header = Menu::create(['location' => Menu::HEADER]);
        $footer = Menu::create(['location' => Menu::FOOTER]);
        MenuItem::create(['menu_id' => $header->id, 'label' => 'Header Unik', 'link_type' => LinkType::HOME]);
        MenuItem::create(['menu_id' => $footer->id, 'label' => 'Footer Unik', 'link_type' => LinkType::HOME]);

        $this->get('/')->assertOk()->assertSee('Header Unik')->assertSee('Footer Unik');
        $this->assertSame(2, Menu::count());
        $this->assertSame(2, MenuItem::count());
        $this->assertSame($header->id, app(NavigationResolver::class)->forLocation(Menu::HEADER)->id);
        $this->assertSame($footer->id, app(NavigationResolver::class)->forLocation(Menu::FOOTER)->id);
    }

    public function test_editor_get_does_not_create_a_menu_and_explicit_create_remains_available(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);

        $this->get(route('filament.admin.resources.menus.index'))
            ->assertRedirect(route('filament.admin.resources.menus.create', ['location' => Menu::HEADER]));
        $this->get(route('filament.admin.resources.menus.footer'))
            ->assertRedirect(route('filament.admin.resources.menus.create', ['location' => Menu::FOOTER]));
        $this->assertSame(0, Menu::count());

        $this->get(route('filament.admin.resources.menus.create', ['location' => Menu::FOOTER]))->assertOk();
        $this->assertSame(0, Menu::count());
    }

    public function test_resolver_preserves_order_and_hierarchy_but_suppresses_ineligible_destinations(): void
    {
        $menu = Menu::create(['location' => Menu::HEADER]);
        $page = Page::create(['title' => 'Halaman Terbit', 'status' => 'published']);
        $future = Page::create(['title' => 'Halaman Mendatang', 'status' => 'published']);
        $withoutDate = Page::create(['title' => 'Halaman Tanpa Tanggal', 'status' => 'published']);
        $draft = Page::create(['title' => 'Halaman Draf', 'status' => 'draft']);
        DB::table('pages')->where('id', $future->id)->update(['published_at' => now()->addDay()]);
        DB::table('pages')->where('id', $withoutDate->id)->update(['published_at' => null]);

        $first = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Pertama', 'link_type' => LinkType::PAGE, 'page_id' => $page->id, 'position' => 0]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Tertutup', 'link_type' => LinkType::PAGE, 'page_id' => $future->id, 'position' => 1]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Terakhir', 'link_type' => LinkType::HOME, 'position' => 2]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Draf Tertutup', 'link_type' => LinkType::PAGE, 'page_id' => $draft->id, 'position' => 3]);
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Tanpa Tanggal', 'link_type' => LinkType::PAGE, 'page_id' => $withoutDate->id, 'position' => 5]);
        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $first->id, 'label' => 'Turunan', 'link_type' => LinkType::CUSTOM, 'custom_url' => 'https://example.test/turunan', 'position' => 0]);
        DB::table('menu_items')->insert(['menu_id' => $menu->id, 'label' => 'Tautan Lama Tidak Aman', 'link_type' => LinkType::CUSTOM->value, 'custom_url' => 'javascript:alert(1)', 'position' => 4, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()]);

        $resolved = app(NavigationResolver::class)->forLocation(Menu::HEADER);
        $this->assertSame(['Pertama', 'Terakhir'], $resolved->items->pluck('label')->all());
        $this->assertSame('Turunan', $resolved->items->first()->children->first()->label);
        $this->assertSame(route('pages.show', $page->slug), $resolved->items->first()->url);
        $this->get('/')->assertOk()->assertDontSee('Halaman Mendatang')->assertDontSee('Draf Tertutup')->assertDontSee('Tanpa Tanggal')->assertDontSee('Tautan Lama Tidak Aman');
    }

    public function test_explicit_footer_create_and_edit_preserve_menu_and_item_identity(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateMenu::class)->fillForm([
            'location' => Menu::FOOTER,
            'items' => [[
                'label' => 'Kontak', 'link_type' => LinkType::CONTACT->value,
                'is_visible' => true,
                'children' => [[
                    'label' => 'Beranda', 'link_type' => LinkType::HOME->value,
                    'is_visible' => true,
                ]],
            ], [
                'label' => 'Peta', 'link_type' => LinkType::MAP->value,
                'is_visible' => true, 'children' => [],
            ]],
        ])->call('create')->assertHasNoFormErrors();

        $menu = Menu::query()->where('location', Menu::FOOTER)->firstOrFail();
        $item = $menu->items()->firstOrFail();
        $child = $item->children()->firstOrFail();
        $second = $menu->items()->whereKeyNot($item->id)->firstOrFail();
        $this->get(route('filament.admin.resources.menus.create', ['location' => Menu::FOOTER]))
            ->assertRedirect(route('filament.admin.resources.menus.footer'));
        $this->get(route('filament.admin.resources.menus.footer'))->assertOk();
        Livewire::test(EditMenu::class, ['record' => $menu->id])->assertHasNoErrors();

        $editor = Livewire::test(EditFooterMenu::class);
        $items = $editor->get('data.items');
        $itemKey = array_key_first($items);
        $childKey = array_key_first($items[$itemKey]['children']);
        $items[$itemKey]['label'] = 'Kontak Desa';
        $items[$itemKey]['children'][$childKey]['label'] = 'Beranda Desa';
        $editor->fillForm(['items' => $items])->call('save')->assertHasNoFormErrors();

        $this->assertSame($menu->id, Menu::query()->where('location', Menu::FOOTER)->firstOrFail()->id);
        $this->assertSame([$item->id, $second->id], $menu->items()->pluck('id')->all());
        $this->assertSame($child->id, $item->children()->firstOrFail()->id);
        $this->assertSame('Kontak Desa', $item->refresh()->label);
        $this->assertSame('Beranda Desa', $child->refresh()->label);
    }

    public function test_admin_global_search_accumulates_navigation_matches(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $results = app(GlobalSearchProvider::class)->getResults('a');
        $navigation = collect($results->getCategories()->get('Halaman & Fitur', []));

        $this->assertGreaterThan(1, $navigation->count());
        $this->assertSame($navigation->pluck('url')->unique()->count(), $navigation->count());
        $this->assertLessThanOrEqual(20, $navigation->count());
    }

    public function test_existing_label_only_preview_overlay_still_reaches_public_navigation(): void
    {
        $state = PreviewStateNormalizer::normalize('menu', [
            'location' => Menu::FOOTER,
            'items' => [['label' => 'Tautan Pratinjau', 'is_visible' => true]],
        ]);
        app()->instance(PreviewContext::class, new PreviewContext(
            'menu', $state, ['location' => Menu::FOOTER], 'edit', [],
        ));

        try {
            $this->get('/')->assertOk()->assertSee('Tautan Pratinjau');
            $this->assertSame(0, Menu::count());
        } finally {
            app()->forgetInstance(PreviewContext::class);
        }
    }
}
