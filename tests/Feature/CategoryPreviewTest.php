<?php

use App\Filament\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Resources\LocationCategories\Pages\CreateLocationCategory;
use App\Filament\Resources\NewsCategories\Pages\CreateNewsCategory;
use App\Models\Admin;
use App\Models\DocumentCategory;
use App\Models\LocationCategory;
use App\Models\NewsCategory;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('all visual categories provide non-persisting card previews', function (string $page, string $model, array $state, string $expected) {
    $admin = Admin::factory()->create();
    $before = $model::count();
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');

    Livewire::actingAs($admin)->test($page)
        ->fillForm($state)
        ->mountAction($preview)
        ->assertMountedActionModalSee($expected)
        ->assertMountedActionModalSee('<script>deskripsi</script>')
        ->assertMountedActionModalDontSeeHtml('<script>');

    expect($model::count())->toBe($before);
})->with([
    'location category' => [CreateLocationCategory::class, LocationCategory::class, ['name' => 'Pelayanan', 'description' => '<script>deskripsi</script>', 'is_active' => true], 'Aktif'],
    'news category' => [CreateNewsCategory::class, NewsCategory::class, ['name' => 'Kabar Desa', 'description' => '<script>deskripsi</script>'], 'Kabar Desa'],
    'document category' => [CreateDocumentCategory::class, DocumentCategory::class, ['name' => 'Peraturan', 'description' => '<script>deskripsi</script>'], 'Filter Dokumen'],
]);
