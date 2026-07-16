<?php

use App\Models\Admin;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GalleryAlbum;
use App\Models\Media;
use App\Models\Menu;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access any policy action', function () {
    $models = [
        News::class,
        GalleryAlbum::class,
        Document::class,
        NewsCategory::class,
        DocumentCategory::class,
        Page::class,
        Menu::class,
    ];

    $actions = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];

    foreach ($models as $model) {
        $instance = new $model;
        foreach ($actions as $action) {
            $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser(null)->check($action, $instance));
        }
    }
});

test('admin can access all policy actions', function () {
    $admin = Admin::factory()->create();

    $models = [
        News::class,
        GalleryAlbum::class,
        Document::class,
        NewsCategory::class,
        DocumentCategory::class,
        Page::class,
        Menu::class,
    ];

    $actions = ['viewAny', 'view', 'create', 'update', 'restore', 'forceDelete']; // Delete is tested separately for categories

    foreach ($models as $model) {
        $instance = new $model;
        foreach ($actions as $action) {
            $this->assertTrue(
                app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check($action, $instance),
                "Failed asserting that admin can {$action} {$model}"
            );
        }
    }
});

test('admin cannot delete categories with relationships', function () {
    $admin = Admin::factory()->create();

    $newsCat = NewsCategory::create(['name' => 'Cat 1', 'slug' => 'cat-1']);
    News::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'x', 'news_category_id' => $newsCat->id, 'status' => 'draft',
    ]);

    $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('delete', $newsCat));
    $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('forceDelete', $newsCat));

    $docCat = DocumentCategory::create(['name' => 'Doc 1', 'slug' => 'doc-1']);
    $media = Media::create(['disk' => 'public', 'directory' => 'd', 'filename' => 'f.pdf', 'original_filename' => 'f.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 1, 'processing_status' => 'completed', 'invisible_watermark_status' => 'unsupported']);
    Document::create([
        'title' => 'Test Doc', 'slug' => 'test-doc', 'file_media_id' => $media->id, 'document_category_id' => $docCat->id, 'status' => 'draft',
    ]);

    $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('delete', $docCat));
    $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('forceDelete', $docCat));
});

test('admin can delete categories without relationships', function () {
    $admin = Admin::factory()->create();

    $newsCat = NewsCategory::create(['name' => 'Cat 2', 'slug' => 'cat-2']);
    $docCat = DocumentCategory::create(['name' => 'Doc 2', 'slug' => 'doc-2']);

    $this->assertTrue(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('delete', $newsCat));
    $this->assertTrue(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('delete', $docCat));
});
