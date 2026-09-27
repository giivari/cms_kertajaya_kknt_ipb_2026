<?php

use App\Models\Admin;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\GalleryAlbum;
use App\Models\Media;
use App\Models\News;
use App\Models\NewsCategory;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

        test()->get($routes[$name])->assertRedirect(route('filament.admin.auth.login'));
    });

    test("{$name} authenticated Admin preview observes managed-file availability", function () use ($factory, $name) {
        $model = $factory();
        $model->update(['status' => 'draft']);

        $routes = [
            'news' => '/berita/preview/'.$model->slug,
            'gallery' => '/galeri/preview/'.$model->slug,
            'document' => '/dokumen/preview/'.$model->slug.'/download',
        ];

        $admin = Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]);
        $response = test()->actingAs($admin)->get($routes[$name]);
        $response->assertStatus($name === 'document' ? 404 : 200);
    });
}
