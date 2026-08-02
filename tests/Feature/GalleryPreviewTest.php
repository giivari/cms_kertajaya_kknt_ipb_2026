<?php

use App\Filament\Resources\GalleryAlbums\Pages\CreateGalleryAlbum;
use App\Filament\Resources\GalleryAlbums\Pages\EditGalleryAlbum;
use App\Models\Admin;
use App\Models\GalleryAlbum;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('gallery preview UI stays disabled without creating or changing an album', function () {
    $admin = Admin::factory()->create();
    $album = GalleryAlbum::create(['title' => 'Galeri Lama', 'status' => 'draft'])->refresh();
    $beforeCount = GalleryAlbum::count();
    $beforeState = [$album->title, $album->slug, $album->status, $album->published_at];
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');

    Livewire::actingAs($admin)->test(CreateGalleryAlbum::class)
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview);

    Livewire::actingAs($admin)->test(EditGalleryAlbum::class, ['record' => $album->getRouteKey()])
        ->assertStatus(200)
        ->assertActionDoesNotExist($preview);

    $fresh = $album->fresh();

    expect(GalleryAlbum::count())->toBe($beforeCount)
        ->and([$fresh->title, $fresh->slug, $fresh->status, $fresh->published_at])->toBe($beforeState);
});
