<?php

use App\Enums\LinkType;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Models\Admin;
use App\Models\Menu;
use App\Models\MenuItem;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('menu create and edit preview render visible navigation without persistence', function () {
    $admin = Admin::factory()->create();
    $before = [Menu::count(), MenuItem::count()];
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');
    $items = [
        ['label' => 'Beranda Desa', 'link_type' => LinkType::HOME->value, 'is_visible' => true, 'children' => []],
        ['label' => 'Disembunyikan', 'link_type' => LinkType::MAP->value, 'is_visible' => false, 'children' => []],
    ];

    Livewire::actingAs($admin)->test(CreateMenu::class)
        ->fillForm(['location' => Menu::HEADER, 'items' => $items])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Navigasi Utama')
        ->assertMountedActionModalSee('Desktop')
        ->assertMountedActionModalSee('Mobile')
        ->assertMountedActionModalSee('Beranda Desa')
        ->assertMountedActionModalDontSee('Disembunyikan');

    expect([Menu::count(), MenuItem::count()])->toBe($before);

    $menu = Menu::create(['location' => Menu::FOOTER]);
    Livewire::actingAs($admin)->test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->fillForm(['items' => [['label' => 'Kontak Desa', 'link_type' => LinkType::CONTACT->value, 'is_visible' => true, 'children' => []]]])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Pratinjau Perubahan')
        ->assertMountedActionModalSee('Tautan Cepat')
        ->assertMountedActionModalSee('Kontak Desa');

    expect($menu->fresh()->items)->toHaveCount(0);
});

test('menu form uses full width sections and no repeated repeater heading', function () {
    $source = file_get_contents(app_path('Filament/Resources/Menus/MenuResource.php'));
    $sections = ['Tentang Menu', 'Lokasi Tampilan', 'Tautan yang Ditampilkan', 'Pratinjau Navigasi'];

    expect(substr_count($source, "Section::make('Tautan yang Ditampilkan')"))->toBe(1)
        ->and($source)->not->toContain("->label('Tautan yang Ditampilkan')")
        ->and($source)->toContain("->columns(['default' => 1, 'md' => 2])")
        ->and($source)->toContain('->hiddenLabel()')
        ->and($source)->toContain("'Nama yang Tampil'");

    foreach ($sections as $section) {
        expect($source)->toContain("Section::make('{$section}')");
        expect((bool) preg_match(
            "/Section::make\\('".preg_quote($section, '/')."'\\).*?->columnSpanFull\\(\\)/s",
            $source,
        ))->toBeTrue();
    }
});
