<?php

namespace Tests\Feature;

use App\Enums\LinkType;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menus_render_in_views()
    {
        $headerMenu = Menu::create(['name' => 'Header', 'location' => 'header_menu']);
        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => 'Tentang Kami',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => '/tentang',
        ]);

        $footerMenu = Menu::create(['name' => 'Footer', 'location' => 'footer_menu']);
        MenuItem::create([
            'menu_id' => $footerMenu->id,
            'label' => 'Privacy Policy',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => '/privacy',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Tentang Kami');
        $response->assertSee('/tentang');
        $response->assertSee('Privacy Policy');
        $response->assertSee('/privacy');
    }

    public function test_hierarchical_menus_resolve_children()
    {
        $menu = Menu::create(['name' => 'Main', 'location' => 'main']);
        $parent = MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Parent',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => '#',
        ]);
        $child = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'label' => 'Child',
            'link_type' => LinkType::CUSTOM->value,
            'custom_url' => '/child',
        ]);

        $this->assertEquals(1, $parent->children->count());
        $this->assertEquals('Child', $parent->children->first()->label);
    }
}
