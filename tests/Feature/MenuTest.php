<?php

namespace Tests\Feature;

use App\Enums\LinkType;
use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_menus_render_in_header_mobile_navigation_and_footer(): void
    {
        $headerMenu = Menu::create(['location' => Menu::HEADER]);
        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => 'Profil Desa',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => 'https://example.test/profil',
        ]);

        $footerMenu = Menu::create(['location' => Menu::FOOTER]);
        MenuItem::create([
            'menu_id' => $footerMenu->id,
            'label' => 'Kebijakan Privasi',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => 'https://example.test/privasi',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSeeTextInOrder(['Profil Desa', 'Profil Desa', 'Kebijakan Privasi'])
            ->assertSee('https://example.test/profil', false)
            ->assertSee('https://example.test/privasi', false);
    }

    public function test_hierarchical_menus_resolve_children(): void
    {
        $menu = Menu::create(['location' => Menu::HEADER]);
        $parent = MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Induk',
            'link_type' => LinkType::HOME->value,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'label' => 'Turunan',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => 'https://example.test/turunan',
        ]);

        $this->assertCount(1, $parent->children);
        $this->assertSame('Turunan', $parent->children->first()->label);
    }

    public function test_menu_item_generates_correct_internal_and_page_urls(): void
    {
        $menu = Menu::create(['location' => Menu::HEADER]);
        $page = Page::create([
            'title' => 'Profil Desa',
            'status' => 'published',
        ]);

        $types = [
            LinkType::HOME->value => route('home'),
            LinkType::NEWS_INDEX->value => route('news.index'),
            LinkType::GALLERY_INDEX->value => route('gallery.index'),
            LinkType::DOCUMENT_INDEX->value => route('documents.index'),
            LinkType::MAP->value => route('public.map.index'),
            LinkType::CONTACT->value => route('public.contact.show'),
        ];

        foreach ($types as $type => $expectedUrl) {
            $item = MenuItem::create([
                'menu_id' => $menu->id,
                'label' => $type,
                'link_type' => $type,
            ]);

            $this->assertSame($expectedUrl, $item->url);
        }

        $pageItem = MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Profil Desa',
            'link_type' => LinkType::PAGE->value,
            'page_id' => $page->id,
        ]);

        $this->assertSame(route('pages.show', $page->slug), $pageItem->url);
    }

    public function test_menu_location_is_restricted_and_name_is_automatic(): void
    {
        $menu = Menu::create(['name' => 'Nama diabaikan', 'location' => Menu::HEADER]);

        $this->assertSame('Navigasi Utama', $menu->name);
        $this->assertSame([
            Menu::HEADER => 'Navigasi Utama',
            Menu::FOOTER => 'Kaki Halaman',
        ], Menu::supportedLocations());

        $this->expectException(ValidationException::class);
        Menu::create(['location' => 'tes']);
    }

    public function test_duplicate_menu_location_is_rejected(): void
    {
        Menu::create(['location' => Menu::HEADER]);

        $this->expectException(QueryException::class);
        Menu::create(['location' => Menu::HEADER]);
    }

    public function test_unsafe_external_url_is_rejected(): void
    {
        $menu = Menu::create(['location' => Menu::HEADER]);

        $this->expectException(ValidationException::class);
        MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Tidak Aman',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => 'javascript:alert(1)',
        ]);
    }

    public function test_empty_or_missing_menus_use_safe_navigation_fallback(): void
    {
        Menu::create(['location' => Menu::HEADER]);
        Menu::create(['location' => Menu::FOOTER]);

        $this->get('/')
            ->assertOk()
            ->assertSee(route('home'), false)
            ->assertSee(route('public.map.index'), false)
            ->assertSee(route('public.contact.show'), false);
    }

    public function test_admin_forms_explain_pages_and_menus_and_do_not_use_free_text_location(): void
    {
        $menuSource = file_get_contents((new \ReflectionClass(MenuResource::class))->getFileName());
        $pageSource = file_get_contents((new \ReflectionClass(PageResource::class))->getFileName());

        $this->assertStringNotContainsString("TextInput::make('location')", $menuSource);
        $this->assertStringContainsString("Select::make('location')", $menuSource);
        $this->assertStringContainsString('Menu adalah daftar tautan navigasi.', $menuSource);
        $this->assertStringContainsString('halaman dapat ditambahkan ke Menu.', $pageSource);
    }
}
