<?php

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('news can be created and viewed on the frontend', function () {
    $category = NewsCategory::create(['name' => 'General', 'slug' => 'general']);
    
    $news = News::create([
        'title' => 'Test News',
        'slug' => 'test-news',
        'content' => '<p>Test Content</p>',
        'status' => 'published',
        'published_at' => now(),
        'news_category_id' => $category->id,
        'is_featured' => true,
    ]);

    $response = $this->get('/berita');
    $response->assertStatus(200);
    $response->assertSee('Test News');
});

test('news details can be viewed on frontend', function () {
    $category = NewsCategory::create(['name' => 'General', 'slug' => 'general']);
    
    $news = News::create([
        'title' => 'Test News',
        'slug' => 'test-news',
        'content' => '<p>Test Content</p>',
        'status' => 'published',
        'published_at' => now(),
        'news_category_id' => $category->id,
        'is_featured' => true,
    ]);

    $response = $this->get('/berita/test-news');
    $response->assertStatus(200);
    $response->assertSee('Test Content');
});

test('unpublished news is not visible on frontend', function () {
    News::create([
        'title' => 'Hidden News',
        'slug' => 'hidden-news',
        'content' => '<p>Test</p>',
        'status' => 'archived',
    ]);

    $response = $this->get('/berita');
    $response->assertDontSee('Hidden News');

    $response = $this->get('/berita/hidden-news');
    $response->assertStatus(404);
});
