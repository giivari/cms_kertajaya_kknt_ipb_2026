<?php

use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Models\Admin;
use App\Models\Location;
use App\Models\LocationCategory;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('location preview renders card coordinates and map without persistence', function () {
    $admin = Admin::factory()->create();
    $category = LocationCategory::create(['name' => 'Pelayanan', 'is_active' => true]);
    $before = Location::count();
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');

    Livewire::actingAs($admin)->test(CreateLocation::class)
        ->fillForm([
            'name' => 'Balai Desa Sementara',
            'location_category_id' => $category->id,
            'latitude' => -6.9876543,
            'longitude' => 106.1234567,
            'status' => 'published',
            'sort_order' => 0,
        ])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Balai Desa Sementara')
        ->assertMountedActionModalSee('Pratinjau peta')
        ->assertMountedActionModalSeeHtml('openstreetmap.org/export/embed.html')
        ->assertMountedActionModalSeeHtml('-6.9876543')
        ->assertMountedActionModalSeeHtml('106.1234567');

    expect(Location::count())->toBe($before);

    $location = Location::create([
        'name' => 'Lokasi Lama', 'location_category_id' => $category->id,
        'latitude' => -6.9, 'longitude' => 106.1, 'status' => 'draft', 'sort_order' => 0,
    ]);
    Livewire::actingAs($admin)->test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm(['name' => 'Lokasi Perubahan'])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Pratinjau Perubahan')
        ->assertMountedActionModalSee('Lokasi Perubahan');

    expect($location->fresh()->name)->toBe('Lokasi Lama');
});

test('location preview handles incomplete coordinates', function () {
    $admin = Admin::factory()->create();
    $category = LocationCategory::create(['name' => 'Kategori Sementara', 'is_active' => true]);
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');

    Livewire::actingAs($admin)->test(CreateLocation::class)
        ->fillForm(['name' => 'Belum Lengkap', 'location_category_id' => $category->id])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Lengkapi garis lintang dan garis bujur untuk melihat peta.');
});

test('location preview rejects out of range coordinates without creating a record', function () {
    $admin = Admin::factory()->create();
    $category = LocationCategory::create(['name' => 'Koordinat Tidak Valid', 'is_active' => true]);
    $before = Location::count();
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');

    Livewire::actingAs($admin)->test(CreateLocation::class)
        ->fillForm([
            'name' => 'Lokasi Koordinat Tidak Valid',
            'location_category_id' => $category->id,
            'latitude' => 200,
            'longitude' => 500,
        ])
        ->mountAction($preview)
        ->assertMountedActionModalSee('Lengkapi garis lintang dan garis bujur untuk melihat peta.')
        ->assertMountedActionModalDontSeeHtml('openstreetmap.org/export/embed.html');

    expect(Location::count())->toBe($before);
});
