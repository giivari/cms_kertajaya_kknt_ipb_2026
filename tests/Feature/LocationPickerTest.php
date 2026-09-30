<?php

use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Models\Admin;
use App\Models\Location;
use App\Models\LocationCategory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('admin explicitly searches then selects a result to populate location state', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
        [
            'display_name' => 'Kantor Desa Kertajaya, Kabupaten Sukabumi',
            'lat' => '-6.94567891',
            'lon' => '106.87654329',
        ],
    ])]);
    $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);

    $component = Livewire::actingAs($admin)
        ->test(CreateLocation::class)
        ->set('data.location_picker.query', 'Kantor Desa Kertajaya')
        ->callFormComponentAction('location_picker', 'searchLocations')
        ->assertSet('data.latitude', null)
        ->assertSet('data.longitude', null)
        ->assertSet('data.location_picker.results.0.latitude', '-6.9456789')
        ->assertSet('data.location_picker.results.0.longitude', '106.8765433');

    $component
        ->callFormComponentAction('location_picker', 'selectLocationResult', arguments: ['result' => 0])
        ->assertSet('data.latitude', '-6.9456789')
        ->assertSet('data.longitude', '106.8765433')
        ->assertSet('data.address', 'Kantor Desa Kertajaya, Kabupaten Sukabumi');
});

test('existing location coordinates and editable address are preserved without a search', function () {
    Http::fake();
    $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
    $category = LocationCategory::factory()->create();
    $location = Location::factory()->for($category, 'category')->create([
        'address' => 'Alamat tersimpan oleh operator',
        'latitude' => '-6.9123456',
        'longitude' => '106.7654321',
    ]);

    Livewire::actingAs($admin)
        ->test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->assertSet('data.address', 'Alamat tersimpan oleh operator')
        ->assertSet('data.latitude', '-6.9123456')
        ->assertSet('data.longitude', '106.7654321');

    Http::assertNothingSent();
});
