<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Media;
use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

$categories = [
    'news' => [
        'class' => NewsCategory::class,
        'itemClass' => News::class,
        'foreignKey' => 'news_category_id',
    ],
    'document' => [
        'class' => DocumentCategory::class,
        'itemClass' => Document::class,
        'foreignKey' => 'document_category_id',
    ],
];

foreach ($categories as $type => $config) {
    test("{$type} category unique slugs and collision safe", function () use ($config) {
        $cat1 = $config['class']::create(['name' => 'Same Name']);
        $cat2 = $config['class']::create(['name' => 'Same Name!']);

        expect($cat1->slug)->toBe('same-name');
        expect($cat2->slug)->toBe('same-name-2');

        $cat3 = $config['class']::create(['name' => 'Same Name?']);
        expect($cat3->slug)->toBe('same-name-3');
    });

    test("{$type} category rejection when referenced", function () use ($config) {
        $cat = $config['class']::create(['name' => 'Test']);

        if ($config['itemClass'] === Document::class) {
            $media = Media::factory()->create();
            $config['itemClass']::create(['title' => 'Test', $config['foreignKey'] => $cat->id, 'file_media_id' => $media->id]);
        } else {
            $config['itemClass']::create(['title' => 'Test', 'content' => 'x', $config['foreignKey'] => $cat->id]);
        }

        expect(fn () => $cat->delete())->toThrow(Exception::class);
        expect($cat->fresh())->not->toBeNull();
    });

    test("{$type} category deleted excluded from public", function () use ($config, $type) {
        $cat1 = $config['class']::create(['name' => 'Active']);
        $cat3 = $config['class']::create(['name' => 'Empty Deleted']);
        $cat3->delete();

        if ($config['itemClass'] === Document::class) {
            $media = Media::factory()->create();
            $item1 = $config['itemClass']::create(['title' => 'Item 1', $config['foreignKey'] => $cat1->id, 'status' => 'published', 'published_at' => now(), 'file_media_id' => $media->id]);
        } else {
            $item1 = $config['itemClass']::create(['title' => 'Item 1', 'content' => 'x', $config['foreignKey'] => $cat1->id, 'status' => 'published', 'published_at' => now()]);
        }

        $route = $type === 'news' ? '/berita' : '/dokumen';

        $response = $this->get($route);
        $response->assertDontSee('Empty Deleted');
        $response->assertSee('Active');
    });

    test("{$type} category filtering, pagination, empty filter", function () use ($config, $type) {
        $cat1 = $config['class']::create(['name' => 'Cat One']);
        $cat2 = $config['class']::create(['name' => 'Cat Two']);
        $baseTime = now()->subDays(2)->startOfMinute();

        for ($i = 0; $i < 15; $i++) {
            $publishedAt = $baseTime->copy()->addMinutes($i);

            if ($config['itemClass'] === Document::class) {
                $media = Media::factory()->create();
                $config['itemClass']::create([
                    'title' => "Item A {$i}",
                    $config['foreignKey'] => $cat1->id,
                    'status' => 'published',
                    'published_at' => $publishedAt,
                    'created_at' => $publishedAt,
                    'updated_at' => $publishedAt,
                    'file_media_id' => $media->id,
                ]);

                if ($i < 5) {
                    $media2 = Media::factory()->create();
                    $config['itemClass']::create([
                        'title' => "Item B {$i}",
                        $config['foreignKey'] => $cat2->id,
                        'status' => 'published',
                        'published_at' => $publishedAt,
                        'created_at' => $publishedAt,
                        'updated_at' => $publishedAt,
                        'file_media_id' => $media2->id,
                    ]);
                }
            } else {
                $config['itemClass']::create([
                    'title' => "Item A {$i}",
                    'content' => 'x',
                    $config['foreignKey'] => $cat1->id,
                    'status' => 'published',
                    'published_at' => $publishedAt,
                    'created_at' => $publishedAt,
                    'updated_at' => $publishedAt,
                ]);

                if ($i < 5) {
                    $config['itemClass']::create([
                        'title' => "Item B {$i}",
                        'content' => 'y',
                        $config['foreignKey'] => $cat2->id,
                        'status' => 'published',
                        'published_at' => $publishedAt,
                        'created_at' => $publishedAt,
                        'updated_at' => $publishedAt,
                    ]);
                }
            }
        }

        $route = $type === 'news' ? '/berita' : '/dokumen';

        // Filter Cat One
        $response = $this->get($route.'?category='.$cat1->slug);
        $response->assertSee('Item A 14');
        $response->assertSee('Item A 3');
        $response->assertDontSee('Item A 2');
        $response->assertDontSee('Item A 0');

        for ($i = 0; $i < 5; $i++) {
            $response->assertDontSee("Item B {$i}");
        }

        // Pagination
        $response = $this->get($route.'?category='.$cat1->slug.'&page=2');
        $response->assertStatus(200);
        $response->assertSee('Item A 2');
        $response->assertSee('Item A 1');
        $response->assertSee('Item A 0');
        $response->assertDontSee('Item A 3');

        for ($i = 0; $i < 5; $i++) {
            $response->assertDontSee("Item B {$i}");
        }

        // Empty filter
        $cat3 = $config['class']::create(['name' => 'Cat Three']);
        $response = $this->get($route.'?category='.$cat3->slug);
        $response->assertSee('Belum ada');
    });
}
