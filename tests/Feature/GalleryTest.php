<?php

use App\Models\GalleryAlbum;
use App\Models\GalleryAlbumItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gallery albums can be viewed on frontend', function () {
    $this->withoutExceptionHandling();
    $album = GalleryAlbum::create([
        'title' => 'Test Album',
        'slug' => 'test-album',
        'status' => 'published',
        'published_at' => now(),
        'is_featured' => true,
    ]);

    $response = $this->get('/galeri');
    $response->assertStatus(200);
    $response->assertSee('Test Album');
});

test('gallery album details can be viewed on frontend', function () {
    $album = GalleryAlbum::create([
        'title' => 'Test Album',
        'slug' => 'test-album',
        'status' => 'published',
        'published_at' => now(),
        'is_featured' => true,
    ]);

    $response = $this->get('/galeri/test-album');
    $response->assertStatus(200);
    $response->assertSee('Test Album');
});

test('unpublished gallery albums are not visible', function () {
    GalleryAlbum::create([
        'title' => 'Hidden Album',
        'slug' => 'hidden-album',
        'status' => 'archived',
        'is_featured' => false,
    ]);

    $response = $this->get('/galeri');
    $response->assertDontSee('Hidden Album');

    $response = $this->get('/galeri/hidden-album');
    $response->assertStatus(404);
});
