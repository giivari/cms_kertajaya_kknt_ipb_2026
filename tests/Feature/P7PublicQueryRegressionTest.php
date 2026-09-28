<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\GalleryAlbumItem;
use App\Models\Media;
use App\Models\News;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class P7PublicQueryRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_media_routes_batch_derivative_lookups_without_changing_urls(): void
    {
        $this->withoutVite();

        $media = collect(range(1, 5))->map(function (int $number): Media {
            $item = Media::factory()->create([
                'processing_status' => 'completed',
                'invisible_watermark_status' => 'verified',
            ]);
            $item->derivatives()->create([
                'derivative_type' => 'public',
                'filename' => 'derivatives/'.$item->id.'/generation-'.$number.'.jpg',
                'disk' => 'local',
                'size' => 1024,
                'mime_type' => 'image/jpeg',
            ]);

            return $item;
        });

        foreach (range(0, 2) as $index) {
            News::create([
                'title' => 'P7 Berita '.$index,
                'slug' => 'p7-berita-'.$index,
                'content' => 'Isi berita P7',
                'status' => 'published',
                'published_at' => now()->subDay(),
                'featured_media_id' => $media[$index]->id,
            ]);
        }

        foreach (range(0, 1) as $index) {
            $album = GalleryAlbum::create([
                'title' => 'P7 Album '.$index,
                'slug' => 'p7-album-'.$index,
                'status' => 'published',
                'published_at' => now()->subDay(),
                'cover_media_id' => $media[$index + 3]->id,
            ]);
            GalleryAlbumItem::create([
                'gallery_album_id' => $album->id,
                'media_id' => $media[$index + 3]->id,
                'position' => 1,
            ]);
        }

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'select ')
                && str_contains(strtolower($query->sql), 'media_derivatives')) {
                $queries[] = $query->sql;
            }
        });

        $routes = [
            '/' => [2, 0],
            '/berita' => [1, 0],
            '/galeri' => [1, 3],
            '/galeri/p7-album-0' => [1, 3],
        ];

        $counts = [];
        foreach ($routes as $path => [, $expectedMediaIndex]) {
            $queries = [];
            $response = $this->get($path)->assertOk();
            $response->assertSee(route('media.derivative', $media[$expectedMediaIndex]), false);
            $counts[$path] = count($queries);
        }

        foreach ($routes as $path => [$maxDerivativeQueries]) {
            $this->assertLessThanOrEqual(
                $maxDerivativeQueries,
                $counts[$path],
                json_encode($counts, JSON_THROW_ON_ERROR),
            );
        }
    }
}
