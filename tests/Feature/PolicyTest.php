<?php

use App\Models\Admin;
use App\Models\News;
use App\Models\GalleryAlbum;
use App\Models\Document;
use App\Models\NewsCategory;
use App\Models\DocumentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot access any policy action', function () {
    $models = [
        News::class,
        GalleryAlbum::class,
        Document::class,
        NewsCategory::class,
        DocumentCategory::class,
    ];

    foreach ($models as $model) {
        $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser(null)->check('viewAny', $model));
    }
});

test('admin can viewAny all models', function () {
    $admin = Admin::factory()->create();
    
    $models = [
        News::class,
        GalleryAlbum::class,
        Document::class,
        NewsCategory::class,
        DocumentCategory::class,
    ];

    foreach ($models as $model) {
        $this->assertTrue(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('viewAny', $model));
    }
});

test('admin cannot delete categories with relationships', function () {
    $admin = Admin::factory()->create();
    
    $newsCat = NewsCategory::create(['name' => 'Cat 1', 'slug' => 'cat-1']);
    News::create([
        'title' => 'Test', 'slug' => 'test', 'content' => 'x', 'news_category_id' => $newsCat->id, 'status' => 'draft'
    ]);
    
    $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('delete', $newsCat));
    $this->assertFalse(app('Illuminate\Contracts\Auth\Access\Gate')->forUser($admin)->check('forceDelete', $newsCat));

    $docCat = DocumentCategory::create(['name' => 'Doc 1', 'slug' => 'doc-1']);
    $media = App\Models\Media::create(['disk'=>'public','directory'=>'d','filename'=>'f.pdf','original_filename'=>'f.pdf','mime_type'=>'application/pdf','extension'=>'pdf','size'=>1,'processing_status'=>'completed','invisible_watermark_status'=>'unsupported']);
    Document::create([
        'title' => 'Test Doc', 'slug' => 'test-doc', 'file_media_id' => $media->id, 'document_category_id' => $docCat->id, 'status' => 'draft'
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
