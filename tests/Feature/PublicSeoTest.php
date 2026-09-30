<?php

use App\Enums\PageStatus;
use App\Models\Admin;
use App\Models\GalleryAlbum;
use App\Models\Location;
use App\Models\LocationCategory;
use App\Models\News;
use App\Models\Page;
use App\Services\SettingsService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('homepage exposes a clean configured title canonical and website schema', function () {
    SettingsService::setMany(['village_name' => 'Desa Kertajaya']);

    $response = $this->get(route('home'))->assertOk();
    $html = $response->getContent();

    $response
        ->assertSee('<title>Desa Kertajaya</title>', false)
        ->assertDontSee('<title>Beranda | Desa Kertajaya</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false);

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
    $schemas = collect($matches[1] ?? [])
        ->map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    $schema = $schemas->firstWhere('@type', 'WebSite');

    expect(substr_count($html, 'rel="canonical"'))->toBe(1)
        ->and($schema)->not->toBeNull()
        ->and($schema['@context'])->toBe('https://schema.org')
        ->and($schema['@type'])->toBe('WebSite')
        ->and($schema['name'])->toBe('Desa Kertajaya')
        ->and($schema['url'])->toBe(rtrim(route('home'), '/').'/');
});

test('public internal page keeps its descriptive title and self canonical while preview has none', function () {
    SettingsService::setMany(['village_name' => 'Desa Kertajaya']);
    $page = Page::create([
        'title' => 'Tentang Desa',
        'slug' => 'tentang-desa',
        'status' => PageStatus::PUBLISHED,
        'published_at' => now()->subMinute(),
    ]);

    $this->get(route('pages.show', $page->slug))
        ->assertOk()
        ->assertSee('<title>Tentang Desa | Desa Kertajaya</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('pages.show', $page->slug).'">', false);

    $admin = Admin::factory()->create([
        'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
    ]);
    $this->actingAs($admin)
        ->withSession(['session_created_at' => time()])
        ->get(route('pages.preview', $page->slug))
        ->assertOk()
        ->assertDontSee('rel="canonical"', false);
});

test('sitemap contains only eligible public content', function () {
    $publishedPage = Page::create([
        'title' => 'Halaman Terbit', 'slug' => 'halaman-terbit',
        'status' => PageStatus::PUBLISHED, 'published_at' => now()->subMinute(),
    ]);
    $draftPage = Page::create([
        'title' => 'Halaman Draf', 'slug' => 'halaman-draf',
        'status' => PageStatus::DRAFT,
    ]);
    $futurePage = Page::create([
        'title' => 'Halaman Masa Depan', 'slug' => 'halaman-masa-depan',
        'status' => PageStatus::PUBLISHED, 'published_at' => now()->addDay(),
    ]);
    $publishedNews = News::create([
        'title' => 'Berita Terbit', 'slug' => 'berita-terbit',
        'content' => 'Isi berita terbit.',
        'status' => 'published', 'published_at' => now()->subMinute(),
    ]);
    $archivedNews = News::create([
        'title' => 'Berita Arsip', 'slug' => 'berita-arsip',
        'content' => 'Isi berita arsip.',
        'status' => 'archived', 'published_at' => now()->subMinute(),
    ]);
    $publishedGallery = GalleryAlbum::create([
        'title' => 'Galeri Terbit', 'slug' => 'galeri-terbit',
        'status' => 'published', 'published_at' => now()->subMinute(),
    ]);
    $category = LocationCategory::factory()->create(['is_active' => true]);
    $publishedLocation = Location::factory()->for($category, 'category')->create([
        'name' => 'Lokasi Terbit', 'slug' => 'lokasi-terbit',
    ]);
    $draftLocation = Location::factory()->for($category, 'category')->draft()->create([
        'name' => 'Lokasi Draf', 'slug' => 'lokasi-draf',
    ]);

    $response = $this->get(route('sitemap'))->assertOk();
    $xml = $response->getContent();

    expect($response->headers->get('Content-Type'))->toStartWith('application/xml')
        ->and($xml)->toContain(rtrim(route('home'), '/').'/')
        ->and($xml)->toContain(route('pages.show', $publishedPage->slug))
        ->and($xml)->toContain(route('news.show', $publishedNews->slug))
        ->and($xml)->toContain(route('gallery.show', $publishedGallery->slug))
        ->and($xml)->toContain(route('public.map.show', $publishedLocation))
        ->and($xml)->not->toContain(route('pages.show', $draftPage->slug))
        ->and($xml)->not->toContain(route('pages.show', $futurePage->slug))
        ->and($xml)->not->toContain(route('news.show', $archivedNews->slug))
        ->and($xml)->not->toContain(route('public.map.show', $draftLocation))
        ->and($xml)->not->toContain('desa-dashboard')
        ->and($xml)->not->toContain('/preview/');
});

test('robots allows crawling and declares the production sitemap', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('User-agent: *')
        ->and($robots)->toContain('Disallow:')
        ->and($robots)->toContain('Sitemap: https://desakertajaya.web.id/sitemap.xml');
});
