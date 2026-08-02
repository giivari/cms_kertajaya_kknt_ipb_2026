<?php

use App\Models\Admin;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GalleryAlbum;
use App\Models\Media;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::get('/login', fn () => 'login')->name('login');
});

$models = [
    'news' => fn () => News::create(['title' => 'Test News', 'content' => 'x', 'news_category_id' => NewsCategory::firstOrCreate(['name' => 'Cat'])->id]),
    'gallery' => fn () => GalleryAlbum::create(['title' => 'Test Gallery']),
    'document' => fn () => Document::create(['title' => 'Test Document', 'document_category_id' => DocumentCategory::firstOrCreate(['name' => 'Cat'])->id, 'file_media_id' => Media::factory()->create()->id]),
];

foreach ($models as $name => $factory) {
    test("{$name} defaults to draft", function () use ($factory) {
        $model = $factory();
        expect($model->status)->toBe('draft');
    });

    test("{$name} sets published_at when published", function () use ($factory) {
        $model = $factory();
        $model->update(['status' => 'published']);
        expect($model->published_at)->not->toBeNull();
    });

    test("{$name} keeps published_at when published content is edited", function () use ($factory) {
        $model = $factory();
        $model->update(['status' => 'published']);
        $model->refresh();
        $publishedAt = $model->published_at->copy();

        $model->update(['title' => $model->title.' updated']);
        $model->refresh();

        expect($model->published_at->equalTo($publishedAt))->toBeTrue();
    });

    test("{$name} soft deletes and restores", function () use ($factory) {
        $model = $factory();
        $model->delete();
        expect($model->trashed())->toBeTrue();
        $model->restore();
        expect($model->trashed())->toBeFalse();
    });

    test("{$name} generates unique safe slugs", function () use ($factory) {
        $model1 = $factory();
        $model2 = $factory();
        expect($model1->slug)->not->toBe($model2->slug);
    });

    test("{$name} guest preview denied", function () use ($factory, $name) {
        $model = $factory();
        $model->update(['status' => 'draft']);

        $routes = [
            'news' => '/berita/preview/'.$model->slug,
            'gallery' => '/galeri/preview/'.$model->slug,
            'document' => '/dokumen/preview/'.$model->slug.'/download',
        ];

        try {
            test()->withoutExceptionHandling();
            $response = test()->get($routes[$name]);
            $response->assertStatus(302);
        } catch (RouteNotFoundException $e) {
            expect($e->getMessage())->toContain('login');
        } catch (AuthenticationException $e) {
            expect(true)->toBeTrue();
        }
    });

    test("{$name} authenticated Admin preview allowed", function () use ($factory, $name) {
        $model = $factory();
        $model->update(['status' => 'draft']);

        if ($name === 'document') {
            $media = Media::factory()->create(['processing_status' => 'completed', 'invisible_watermark_status' => 'verified']);
            $media->derivatives()->create(['derivative_type' => 'public', 'disk' => 'local', 'filename' => 'test.pdf', 'extension' => 'pdf', 'size' => 1024, 'mime_type' => 'application/pdf']);
            $model->update(['file_media_id' => $media->id]);
            Storage::fake('local');
            Storage::disk('local')->put('test.pdf', 'pdf');
        }

        $routes = [
            'news' => '/berita/preview/'.$model->slug,
            'gallery' => '/galeri/preview/'.$model->slug,
            'document' => '/dokumen/preview/'.$model->slug.'/download',
        ];

        $admin = Admin::factory()->create();
        $response = test()->actingAs($admin)->get($routes[$name]);
        $response->assertStatus(200);
    });
}
