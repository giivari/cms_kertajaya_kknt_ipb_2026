<?php

use App\Models\Admin;
use App\Models\Location;
use App\Models\LocationCategory;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createApprovedLocationImage(): Media
{
    $media = Media::factory()->create([
        'mime_type' => 'image/jpeg',
        'extension' => 'jpg',
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'verified',
    ]);

    $media->derivatives()->create([
        'derivative_type' => 'public',
        'disk' => 'public',
        'filename' => 'map-public-'.$media->id.'.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 512,
    ]);

    return $media;
}

test('map page can be opened', function () {
    $this->get(route('public.map.index'))
        ->assertOk()
        ->assertSee('Peta Desa Kertajaya');
});

test('published location appears', function () {
    $location = Location::factory()->create(['name' => 'Balai Desa Kertajaya']);

    $this->get(route('public.map.index'))
        ->assertOk()
        ->assertSee($location->name);
});

test('draft unpublished archived and future locations do not appear', function () {
    Location::factory()->draft()->create(['name' => 'Lokasi Draft']);
    Location::factory()->archived()->create(['name' => 'Lokasi Arsip']);
    Location::factory()->create(['name' => 'Lokasi Masa Depan', 'published_at' => now()->addDay()]);

    $this->get(route('public.map.index'))
        ->assertDontSee('Lokasi Draft')
        ->assertDontSee('Lokasi Arsip')
        ->assertDontSee('Lokasi Masa Depan');
});

test('location in inactive category does not appear', function () {
    $category = LocationCategory::factory()->inactive()->create();
    Location::factory()->for($category, 'category')->create(['name' => 'Lokasi Tersembunyi']);

    $this->get(route('public.map.index'))
        ->assertDontSee('Lokasi Tersembunyi')
        ->assertDontSee($category->name);
});

test('invalid coordinates are rejected', function (string $field, float $value) {
    expect(fn () => Location::factory()->create([$field => $value]))
        ->toThrow(ValidationException::class);
})->with([
    'latitude below minimum' => ['latitude', -90.1],
    'latitude above maximum' => ['latitude', 90.1],
    'longitude below minimum' => ['longitude', -180.1],
    'longitude above maximum' => ['longitude', 180.1],
]);

test('soft deleted location does not appear', function () {
    $location = Location::factory()->create(['name' => 'Lokasi Dihapus']);
    $location->delete();

    $this->get(route('public.map.index'))->assertDontSee('Lokasi Dihapus');
});

test('category filter only shows matching locations', function () {
    $health = LocationCategory::factory()->create(['name' => 'Kesehatan', 'slug' => 'kesehatan']);
    $education = LocationCategory::factory()->create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);
    Location::factory()->for($health, 'category')->create(['name' => 'Puskesmas Desa']);
    Location::factory()->for($education, 'category')->create(['name' => 'Sekolah Dasar']);

    $this->get(route('public.map.index', ['category' => 'kesehatan']))
        ->assertSee('Puskesmas Desa')
        ->assertDontSee('Sekolah Dasar');
});

test('location list and popup data contain correct detail link', function () {
    $location = Location::factory()->create(['name' => 'Kantor Desa']);

    $this->get(route('public.map.index'))
        ->assertSee('Kantor Desa')
        ->assertSee(route('public.map.show', $location))
        ->assertSee('data-name="Kantor Desa"', false);

    $this->get(route('public.map.show', $location))
        ->assertOk()
        ->assertSee('Kantor Desa');
});

test('public visibility is identical for map list detail and model check', function () {
    $visible = Location::factory()->create([
        'name' => 'Lokasi Terlihat Konsisten',
        'media_id' => null,
    ]);

    expect($visible->isPubliclyVisible())->toBeTrue()
        ->and(Location::query()->publiclyVisible()->whereKey($visible->getKey())->exists())->toBeTrue();

    $this->get(route('public.map.index'))
        ->assertOk()
        ->assertSee($visible->name)
        ->assertSee('data-name="'.$visible->name.'"', false);

    $this->get(route('public.map.show', $visible))
        ->assertOk()
        ->assertSee($visible->name);
});

test('locations excluded from map list also return not found on detail', function (string $state) {
    $location = match ($state) {
        'draft' => Location::factory()->draft()->create(),
        'archived' => Location::factory()->archived()->create(),
        'future' => Location::factory()->create(['published_at' => now()->addDay()]),
        'inactive category' => Location::factory()
            ->for(LocationCategory::factory()->inactive(), 'category')
            ->create(),
        'soft deleted' => tap(Location::factory()->create(), fn (Location $location) => $location->delete()),
    };

    expect(Location::query()->publiclyVisible()->whereKey($location->getKey())->exists())->toBeFalse();

    $this->get(route('public.map.index'))->assertDontSee($location->name);
    $this->get(route('public.map.show', $location))->assertNotFound();
})->with(['draft', 'archived', 'future', 'inactive category', 'soft deleted']);

test('unverified media is not displayed', function () {
    $media = Media::factory()->create([
        'mime_type' => 'image/jpeg',
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'pending',
    ]);
    $location = Location::factory()->create(['media_id' => $media->id]);

    $this->get(route('public.map.index'))
        ->assertSee($location->name)
        ->assertDontSee($media->url);

    $approvedMedia = createApprovedLocationImage();
    $approvedLocation = Location::factory()->create(['media_id' => $approvedMedia->id]);

    $this->get(route('public.map.index'))
        ->assertSee($approvedLocation->name)
        ->assertSee($approvedMedia->url);
});

test('admin location resources require authentication', function () {
    foreach ([
        'filament.admin.resources.locations.index',
        'filament.admin.resources.location-categories.index',
    ] as $routeName) {
        $this->get(route($routeName))->assertRedirect();
    }

    $admin = Admin::factory()->create([
        'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
    ]);
    $this->actingAs($admin)->withSession(['session_created_at' => time()]);

    foreach ([
        'filament.admin.resources.locations.index',
        'filament.admin.resources.location-categories.index',
    ] as $routeName) {
        $this->get(route($routeName))
            ->assertOk();
    }
});

test('category in use cannot be deleted', function () {
    $admin = Admin::factory()->create();
    $category = LocationCategory::factory()->create();
    Location::factory()->for($category, 'category')->create();

    expect(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('delete', $category))->toBeFalse();
    expect(fn () => $category->delete())->toThrow(Exception::class);
});

test('dangerous text is escaped and not executed', function () {
    $dangerous = '<script>alert("maps-xss")</script>';
    Location::factory()->create([
        'name' => 'Lokasi Aman',
        'address' => null,
        'short_description' => $dangerous,
    ]);

    $this->get(route('public.map.index'))
        ->assertOk()
        ->assertDontSee($dangerous, false)
        ->assertSee('&lt;script&gt;', false);
});
