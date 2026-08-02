<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GalleryAlbum;
use App\Models\Location;
use App\Models\LocationCategory;
use App\Models\Media;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function slugManagedModels(): array
{
    return [
        'location' => fn (string $name) => Location::factory()->create(['name' => $name, 'slug' => null]),
        'location category' => fn (string $name) => LocationCategory::factory()->create(['name' => $name, 'slug' => null]),
        'news' => fn (string $name) => News::create(['title' => $name, 'content' => 'Konten']),
        'news category' => fn (string $name) => NewsCategory::create(['name' => $name]),
        'document' => fn (string $name) => Document::create(['title' => $name, 'file_media_id' => Media::factory()->create()->id]),
        'document category' => fn (string $name) => DocumentCategory::create(['name' => $name]),
        'gallery album' => fn (string $name) => GalleryAlbum::create(['title' => $name]),
        'page' => fn (string $name) => Page::create(['title' => $name]),
    ];
}

test('managed models generate collision safe slugs and include soft deleted records', function (string $type) {
    $create = slugManagedModels()[$type];

    $first = $create('Balai Desa');
    $second = $create('Balai Desa!');
    $first->delete();
    $third = $create('Balai Desa?');

    expect($first->slug)->toBe('balai-desa')
        ->and($second->slug)->toBe('balai-desa-2')
        ->and($third->slug)->toBe('balai-desa-3');
})->with(array_keys(slugManagedModels()));

test('managed model slugs stay stable when names or titles change', function (string $type) {
    $record = slugManagedModels()[$type]('Nama Awal');
    $slug = $record->slug;
    $attribute = in_array($type, ['location category', 'news category', 'document category'], true) ? 'name' : ($type === 'location' ? 'name' : 'title');

    $record->update([$attribute => 'Nama Baru']);

    expect($record->fresh()->slug)->toBe($slug);
})->with(array_keys(slugManagedModels()));

test('empty slug sources use a safe unique fallback', function () {
    $first = LocationCategory::create(['name' => '!!!']);
    $second = LocationCategory::create(['name' => '???']);

    expect($first->slug)->toBe('item')
        ->and($second->slug)->toBe('item-2');
});

test('admin forms no longer define slug inputs or slug callbacks', function () {
    $forms = [
        app_path('Filament/Resources/Locations/Schemas/LocationForm.php'),
        app_path('Filament/Resources/LocationCategories/Schemas/LocationCategoryForm.php'),
        app_path('Filament/Resources/News/Schemas/NewsForm.php'),
        app_path('Filament/Resources/NewsCategories/Schemas/NewsCategoryForm.php'),
        app_path('Filament/Resources/Documents/Schemas/DocumentForm.php'),
        app_path('Filament/Resources/DocumentCategories/Schemas/DocumentCategoryForm.php'),
        app_path('Filament/Resources/GalleryAlbums/Schemas/GalleryAlbumForm.php'),
        app_path('Filament/Resources/Pages/PageResource.php'),
    ];

    foreach ($forms as $form) {
        $source = file_get_contents($form);

        expect($source)
            ->not->toContain("TextInput::make('slug')")
            ->not->toContain("Hidden::make('slug')")
            ->not->toContain("set('slug'");
    }
});

test('public routes continue to resolve generated slugs', function () {
    $category = LocationCategory::factory()->create();
    $location = Location::factory()->for($category, 'category')->create([
        'name' => 'Balai Pelayanan',
        'slug' => null,
    ]);
    $page = Page::create([
        'title' => 'Profil Wilayah',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get(route('public.map.show', $location))->assertOk();
    $this->get(route('pages.show', $page->slug))->assertOk();
});
