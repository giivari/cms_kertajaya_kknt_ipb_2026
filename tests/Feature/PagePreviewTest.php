<?php

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Admin;
use App\Models\Page;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('page builder create and edit preview render public block partials without persistence', function () {
    $admin = Admin::factory()->create();
    $before = Page::count();
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');
    $sections = [[
        'name' => 'Bagian Sambutan',
        'layout_type' => 'two_columns',
        'is_visible' => true,
        'components' => [[
            'type' => 'heading',
            'data' => ['text' => 'Selamat Datang', 'level' => 'h2', 'alignment' => 'left', 'is_visible' => true],
        ], [
            'type' => 'rich_text',
            'data' => [
                'content' => '<p>Paragraf aman</p><script>alert("xss")</script><img src="x" onerror="alert(\'xss\')">',
                'is_visible' => true,
            ],
        ], [
            'type' => 'cta_button',
            'data' => ['text' => 'Tombol Aman', 'url' => 'javascript:alert(1)', 'style' => 'primary', 'is_visible' => true],
        ], [
            'type' => 'card_grid',
            'data' => [
                'cards' => [[
                    'title' => 'Kartu Aman',
                    'description' => 'Deskripsi kartu',
                    'link_url' => 'javascript:alert(2)',
                ]],
                'is_visible' => true,
            ],
        ]],
    ]];

    Livewire::actingAs($admin)->test(CreatePage::class)
        ->fillForm(['title' => 'Halaman Sementara', 'excerpt' => 'Ringkasan', 'status' => 'draft', 'builder_sections' => $sections])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Halaman Sementara')
        ->assertMountedActionModalSee('Selamat Datang')
        ->assertMountedActionModalSee('Paragraf aman')
        ->assertMountedActionModalSee('Tombol Aman')
        ->assertMountedActionModalSee('Kartu Aman')
        ->assertMountedActionModalDontSeeHtml('<script')
        ->assertMountedActionModalDontSeeHtml('onerror=')
        ->assertMountedActionModalDontSeeHtml('javascript:')
        ->assertMountedActionModalSeeHtml('href="#"');

    expect(Page::count())->toBe($before);

    $page = Page::create(['title' => 'Halaman Lama', 'status' => 'draft']);
    Livewire::actingAs($admin)->test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['title' => 'Halaman Perubahan', 'builder_sections' => $sections])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Pratinjau Perubahan')
        ->assertMountedActionModalSee('Halaman Perubahan');

    expect($page->fresh()->title)->toBe('Halaman Lama');
});
