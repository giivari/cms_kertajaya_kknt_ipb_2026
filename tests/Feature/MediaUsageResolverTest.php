<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GalleryAlbum;
use App\Models\GalleryAlbumItem;
use App\Models\Media;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('news featured image protects media', function () {
    $media = Media::factory()->create(['processing_status' => 'completed']);
    $cat = NewsCategory::create(['name' => 'Cat 1', 'slug' => 'cat-1']);

    $news = News::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'x',
        'news_category_id' => $cat->id, 'featured_media_id' => $media->id,
    ]);

    expect(fn () => $media->delete())->toThrow(Exception::class, 'Cannot delete media because it is currently in use by other content.');

    $news->delete(); // Soft delete

    // The requirement says: soft-deleted content does not incorrectly retain references
    // unless the approved retention rule requires it.
    // Our NewsMediaUsageResolver ignores soft-deleted news.
    $media->delete(); // Should succeed now
    expect($media->trashed())->toBeTrue();
});

test('gallery cover protects media', function () {
    $media = Media::factory()->create(['processing_status' => 'completed']);
    $album = GalleryAlbum::create([
        'title' => 'Test', 'slug' => 'test', 'cover_media_id' => $media->id,
    ]);

    expect(fn () => $media->delete())->toThrow(Exception::class, 'Cannot delete media because it is currently in use by other content.');

    $album->forceDelete();
    $media->delete();
    expect($media->trashed())->toBeTrue();
});

test('gallery item protects media', function () {
    $media = Media::factory()->create(['processing_status' => 'completed']);
    $album = GalleryAlbum::create(['title' => 'Test', 'slug' => 'test']);
    $item = GalleryAlbumItem::create(['gallery_album_id' => $album->id, 'media_id' => $media->id, 'position' => 1]);

    expect(fn () => $media->delete())->toThrow(Exception::class, 'Cannot delete media because it is currently in use by other content.');

    $item->delete();
    $media->delete();
    expect($media->trashed())->toBeTrue();
});

test('document pdf protects media', function () {
    $media = Media::factory()->create(['processing_status' => 'completed']);
    $cat = DocumentCategory::create(['name' => 'Cat 1', 'slug' => 'cat-1']);
    $doc = Document::create([
        'title' => 'Test Doc', 'slug' => 'test-doc', 'document_category_id' => $cat->id, 'file_media_id' => $media->id,
    ]);

    expect(fn () => $media->delete())->toThrow(Exception::class, 'Cannot delete media because it is currently in use by other content.');

    $doc->forceDelete();
    $media->delete();
    expect($media->trashed())->toBeTrue();
});
