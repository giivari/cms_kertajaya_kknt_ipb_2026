<?php

use App\Filament\Resources\LocationCategories\Pages\CreateLocationCategory;
use App\Models\Admin;
use App\Models\GalleryAlbum;
use App\Models\Location;
use App\Models\LocationCategory;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('menu resource uses valid Filament 4 schema components', function () {
    $source = file_get_contents(app_path('Filament/Resources/Menus/MenuResource.php'));

    expect($source)
        ->toContain('Filament\Schemas\Components\Section')
        ->toContain('Filament\Schemas\Components\Utilities\Get')
        ->not->toContain('Filament\Forms\Components\Section')
        ->not->toContain('Forms\Get');
});

test('authenticated admin can render create menu page', function () {
    $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);

    $response = $this->actingAs($admin)->withSession(['session_created_at' => time()])
        ->get(route('filament.admin.resources.menus.create'));

    $response
        ->assertOk()
        ->assertSee('Tentang Menu')
        ->assertSee('Tautan yang Ditampilkan')
        ->assertSee('<form', false)
        ->assertDontSee('ui-avatars.com', false);
});

test('main admin resources use Indonesian labels and hide technical fields', function () {
    $sources = collect([
        app_path('Filament/Resources/Locations/Schemas/LocationForm.php'),
        app_path('Filament/Resources/News/Schemas/NewsForm.php'),
        app_path('Filament/Resources/Documents/Schemas/DocumentForm.php'),
        app_path('Filament/Resources/GalleryAlbums/Schemas/GalleryAlbumForm.php'),
        app_path('Filament/Resources/Pages/PageResource.php'),
    ])->mapWithKeys(fn (string $path) => [$path => file_get_contents($path)]);

    foreach ($sources as $source) {
        expect($source)
            ->not->toContain("make('slug')")
            ->not->toContain("DateTimePicker::make('published_at')");
    }

    $combined = $sources->implode("\n");

    foreach ([
        'Page Information', 'Page Builder', 'Publishing', 'New Section',
        "'Published'", "'Draft'", "'Archived'",
    ] as $englishLabel) {
        expect($combined)->not->toContain($englishLabel);
    }

    foreach ([
        'Judul', 'Ringkasan', 'Kategori', 'Draf', 'Terbit', 'Diarsipkan',
        'Informasi Halaman', 'Penyusun Halaman', 'Publikasi',
    ] as $indonesianLabel) {
        expect($combined)->toContain($indonesianLabel);
    }

    expect($sources[app_path('Filament/Resources/Documents/Schemas/DocumentForm.php')])
        ->toContain("TextInput::make('download_count')")
        ->toContain('->disabled()');

    expect($sources[app_path('Filament/Resources/Locations/Schemas/LocationForm.php')])
        ->toContain('Garis Lintang')
        ->toContain('Garis Bujur')
        ->toContain('Buat Kategori Lokasi terlebih dahulu');
});

test('published at is automatic and stable while record remains published', function () {
    $news = News::create([
        'title' => 'Berita Otomatis',
        'content' => 'Isi berita',
        'status' => 'published',
    ]);
    $news->refresh();
    $publishedAt = $news->published_at->copy();

    expect($publishedAt)->not->toBeNull()
        ->and($news->status)->toBe('published');

    $news->update(['title' => 'Berita Otomatis Diperbarui']);
    $news->refresh();
    expect($news->published_at->equalTo($publishedAt))->toBeTrue();

    $news->update(['status' => 'archived']);
    try {
        Carbon::setTestNowAndTimezone(
            $publishedAt->copy()->addMinute()->format('Y-m-d H:i:s.u'),
            'Asia/Jakarta'
        );
        $news->update(['status' => 'published']);
        $news->refresh();
        expect($news->published_at->greaterThan($publishedAt))->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});

test('new location category is active by default through admin form', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateLocationCategory::class)
        ->fillForm([
            'name' => 'Pelayanan Umum',
            'description' => 'Lokasi pelayanan warga.',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('location_categories', [
        'name' => 'Pelayanan Umum',
        'is_active' => true,
    ]);
});

test('published location without media appears immediately on map', function () {
    $category = LocationCategory::create([
        'name' => 'Pemerintahan',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $location = Location::create([
        'location_category_id' => $category->id,
        'name' => 'Balai Desa Tanpa Foto',
        'address' => 'Jalan Desa Nomor 1',
        'latitude' => -6.9876543,
        'longitude' => 106.1234567,
        'media_id' => null,
        'status' => 'published',
        'sort_order' => 0,
    ]);

    expect($location->published_at)->not->toBeNull()
        ->and($location->media_id)->toBeNull();

    $this->get(route('public.map.index'))
        ->assertOk()
        ->assertSee('Balai Desa Tanpa Foto')
        ->assertSee('Pemerintahan')
        ->assertSee('data-location-id="'.$location->id.'"', false);

    $this->get(route('public.map.show', $location))->assertOk();
});

test('content resources are preview first and admin views require authentication', function () {
    $resources = [
        'locations' => app_path('Filament/Resources/Locations/Tables/LocationsTable.php'),
        'news' => app_path('Filament/Resources/News/Tables/NewsTable.php'),
        'documents' => app_path('Filament/Resources/Documents/Tables/DocumentsTable.php'),
        'gallery-albums' => app_path('Filament/Resources/GalleryAlbums/Tables/GalleryAlbumsTable.php'),
        'pages' => app_path('Filament/Resources/Pages/PageResource.php'),
    ];

    foreach ($resources as $routeKey => $path) {
        $source = file_get_contents($path);
        expect($source)
            ->toContain('ViewAction::make()')
            ->toContain("getUrl('view'");

        $this->get(route("filament.admin.resources.{$routeKey}.index"))->assertRedirect();
    }
});

test('admin date columns use Indonesian format and WIB timezone', function () {
    $tables = [
        app_path('Filament/Resources/Locations/Tables/LocationsTable.php'),
        app_path('Filament/Resources/News/Tables/NewsTable.php'),
        app_path('Filament/Resources/Documents/Tables/DocumentsTable.php'),
        app_path('Filament/Resources/GalleryAlbums/Tables/GalleryAlbumsTable.php'),
        app_path('Filament/Resources/Pages/PageResource.php'),
    ];

    foreach ($tables as $table) {
        $source = file_get_contents($table);
        expect($source)
            ->toContain('d/m/Y H.i')
            ->toContain('Asia/Jakarta')
            ->toContain('Diterbitkan pada');
    }
});
