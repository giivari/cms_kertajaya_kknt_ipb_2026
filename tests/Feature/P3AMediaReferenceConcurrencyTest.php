<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Services\PageBuilderService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PgSql\Connection;
use PgSql\Result;
use Tests\TestCase;

class P3AMediaReferenceConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_reference_guards_and_advisory_lock_coordinate_attach_with_delete(): void
    {
        $media = Media::create($this->mediaAttributes('reference-race.png'));
        [$attach, $deletion] = [$this->pgConnection(), $this->pgConnection()];

        try {
            $expectedTriggers = [
                'guard_pages_featured_media_id',
                'guard_news_featured_media_id',
                'guard_gallery_albums_cover_media_id',
                'guard_gallery_album_items_media_id',
                'guard_documents_file_media_id',
                'guard_documents_thumbnail_media_id',
                'guard_locations_media_id',
            ];
            $triggers = DB::table('pg_trigger')
                ->whereIn('tgname', $expectedTriggers)
                ->pluck('tgname')->all();
            $this->assertEqualsCanonicalizing($expectedTriggers, $triggers);

            // Delete owns the media row first. The reference trigger must wait,
            // then reject the attach after the cleanup state commits.
            $this->query($deletion, 'BEGIN');
            $this->query($deletion, "SELECT id FROM media WHERE id = {$media->id} FOR UPDATE");
            $this->query($deletion, "UPDATE media SET cleanup_status = 'pending' WHERE id = {$media->id}");
            $slug = 'delete-first-reference-race';
            $this->assertTrue(pg_send_query($attach, "INSERT INTO pages (title, slug, featured_media_id, status, is_featured) VALUES ('Race', '{$slug}', {$media->id}, 'draft', false)"));
            usleep(150_000);
            $this->assertTrue(pg_connection_busy($attach), 'Attach must wait while deletion holds the media row lock.');
            $this->query($deletion, 'COMMIT');
            $rejected = $this->awaitResult($attach);
            $this->assertSame('23503', pg_result_error_field($rejected, PGSQL_DIAG_SQLSTATE));
            $this->assertSame(0, DB::table('pages')->where('slug', $slug)->count());

            DB::table('media')->where('id', $media->id)->update(['cleanup_status' => null]);

            // Attach owns the row through the trigger first. A deletion lock must
            // wait, then observe the committed reference before cleanup proceeds.
            $this->query($attach, 'BEGIN');
            $this->query($attach, "INSERT INTO pages (title, slug, featured_media_id, status, is_featured) VALUES ('Race', 'attach-first-reference-race', {$media->id}, 'draft', false)");
            $this->assertTrue(pg_send_query($deletion, "SELECT id FROM media WHERE id = {$media->id} FOR UPDATE"));
            usleep(150_000);
            $this->assertTrue(pg_connection_busy($deletion), 'Deletion must wait while attach owns the media row lock.');
            $this->query($attach, 'COMMIT');
            $this->assertSame(PGSQL_TUPLES_OK, pg_result_status($this->awaitResult($deletion)));
            $this->assertSame(1, DB::table('pages')->where('featured_media_id', $media->id)->count());

            // JSON/settings writers use the same transaction-scoped advisory key.
            $this->query($attach, 'BEGIN');
            $this->query($attach, "SELECT pg_advisory_xact_lock(hashtextextended('managed-media:{$media->id}', 0))");
            $this->query($deletion, 'BEGIN');
            $this->assertTrue(pg_send_query($deletion, "SELECT pg_advisory_xact_lock(hashtextextended('managed-media:{$media->id}', 0))"));
            usleep(150_000);
            $this->assertTrue(pg_connection_busy($deletion), 'Reference and deletion advisory locks must serialize.');
            $this->query($attach, 'COMMIT');
            $this->assertSame(PGSQL_TUPLES_OK, pg_result_status($this->awaitResult($deletion)));
            $this->query($deletion, 'COMMIT');

            $blocked = Media::create($this->mediaAttributes('archived-reference.png'));
            $blocked->delete();
            $this->expectValidationFailure(fn () => SettingsService::set('hero_image', $blocked->id));

            $page = Page::create(['title' => 'Reference guard page', 'slug' => 'reference-guard-page']);
            $this->expectValidationFailure(fn () => app(PageBuilderService::class)->saveSectionsAndComponents($page, [[
                'name' => 'Blocked image',
                'components' => [[
                    'type' => 'image',
                    'data' => ['media_id' => $blocked->id],
                ]],
            ]]));
            $this->assertSame(0, $page->sections()->count());
        } finally {
            @pg_query($attach, 'ROLLBACK');
            @pg_query($deletion, 'ROLLBACK');
            pg_close($attach);
            pg_close($deletion);
        }
    }

    private function pgConnection(): Connection
    {
        $config = config('database.connections.pgsql');
        $parts = [
            'host='.str_replace(' ', '\\ ', (string) $config['host']),
            'port='.(int) $config['port'],
            'dbname='.str_replace(' ', '\\ ', (string) $config['database']),
            'user='.str_replace(' ', '\\ ', (string) $config['username']),
            "password='".str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $config['password'])."'",
        ];
        $connection = pg_connect(implode(' ', $parts), PGSQL_CONNECT_FORCE_NEW);
        $this->assertInstanceOf(Connection::class, $connection);

        return $connection;
    }

    private function query(Connection $connection, string $sql): Result
    {
        $result = pg_query($connection, $sql);
        $this->assertInstanceOf(Result::class, $result, pg_last_error($connection));

        return $result;
    }

    private function awaitResult(Connection $connection): Result
    {
        while (pg_connection_busy($connection)) {
            pg_consume_input($connection);
            usleep(10_000);
        }
        $result = pg_get_result($connection);
        $this->assertInstanceOf(Result::class, $result, pg_last_error($connection));

        return $result;
    }

    private function expectValidationFailure(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Archived media reference must be rejected.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }

    /** @return array<string, mixed> */
    private function mediaAttributes(string $filename): array
    {
        return [
            'disk' => 'local', 'directory' => 'originals', 'filename' => $filename,
            'original_filename' => $filename, 'mime_type' => 'image/png', 'extension' => 'png',
            'size' => 1, 'metadata' => [], 'processing_status' => 'completed',
            'invisible_watermark_status' => 'verified',
        ];
    }
}
