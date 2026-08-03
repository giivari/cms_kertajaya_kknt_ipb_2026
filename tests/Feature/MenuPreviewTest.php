<?php

use App\Enums\LinkType;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Models\Admin;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\Preview\PreviewTokenStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => config(['preview.ui_enabled' => true]));

test('menu create and edit preview render visible navigation without persistence', function () {
    $admin = Admin::factory()->create();
    $before = [Menu::count(), MenuItem::count()];
    $preview = \Filament\Actions\Testing\TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');
    $items = [
        ['label' => 'Beranda Desa', 'link_type' => LinkType::HOME->value, 'is_visible' => true, 'children' => []],
        ['label' => 'Disembunyikan', 'link_type' => LinkType::MAP->value, 'is_visible' => false, 'children' => []],
    ];

    $lw = Livewire::actingAs($admin)->test(CreateMenu::class)
        ->fillForm(['location' => Menu::HEADER, 'items' => $items])
        ->callAction($preview);
        
    $lw->assertRedirectContains('/preview-shell/');

    // Get the session ID AFTER Livewire requests
    $this->startSession();
    $session = app('session.store');
    $currentSessionId = $session->getId();
    $session->save();
    $this->withCookie($session->getName(), $currentSessionId);

    $store = app(PreviewTokenStore::class);
    $payload = [
        'version' => 1,
        'type' => 'menu',
        'mode' => 'create',
        'record_id' => null,
        'state' => \App\Filament\Support\PreviewStateNormalizer::normalize('menu', ['location' => Menu::HEADER, 'items' => $items]),
        'snapshot' => null,
    ];
    $token = $store->create($admin->id, $currentSessionId, 'menu', $payload);

    $this->withoutExceptionHandling();
    $response = $this->actingAs($admin)->get(route('admin.preview.show', $token));
    
    // In PublicController@index, the menu is rendered via a view composer that reads from SettingsService/Menu
    $response->assertSee('Beranda Desa')
             ->assertDontSee('Disembunyikan');

    expect([Menu::count(), MenuItem::count()])->toBe($before);

    $menu = Menu::create(['location' => Menu::FOOTER]);
    $lwEdit = Livewire::actingAs($admin)->test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->fillForm(['items' => [['label' => 'Kontak Desa', 'link_type' => LinkType::CONTACT->value, 'is_visible' => true, 'children' => []]]])
        ->callAction($preview);

    $lwEdit->assertRedirectContains('/preview-shell/');

    $payloadEdit = [
        'version' => 1,
        'type' => 'menu',
        'mode' => 'edit',
        'record_id' => $menu->id,
        'state' => \App\Filament\Support\PreviewStateNormalizer::normalize('menu', ['items' => [['label' => 'Kontak Desa', 'link_type' => LinkType::CONTACT->value, 'is_visible' => true, 'children' => []]]]),
        'snapshot' => $menu->getAttributes(),
    ];
    $tokenEdit = $store->create($admin->id, $currentSessionId, 'menu', $payloadEdit);

    $responseEdit = $this->actingAs($admin)->get(route('admin.preview.show', $tokenEdit));
    $responseEdit->assertSee('Kontak Desa');

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
