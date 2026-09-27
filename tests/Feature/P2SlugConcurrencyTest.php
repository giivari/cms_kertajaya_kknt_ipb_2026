<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class P2SlugRacePage extends Page
{
    protected $table = 'pages';

    /** @var null|\Closure(string): void */
    public static ?\Closure $afterCandidate = null;

    public static function generateUniqueSlug(?string $source, ?int $exceptId = null): string
    {
        $slug = parent::generateUniqueSlug($source, $exceptId);

        if (static::$afterCandidate) {
            $callback = static::$afterCandidate;
            static::$afterCandidate = null;
            $callback($slug);
        }

        return $slug;
    }
}

class P2SlugConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        P2SlugRacePage::$afterCandidate = null;
        DB::disconnect('p2_slug_racer');
        parent::tearDown();
    }

    public function test_slug_collision_retries_after_a_real_second_connection_commits(): void
    {
        $this->configureRacer();
        $this->raceNextCandidate();

        $page = new P2SlugRacePage(['title' => 'P2 concurrent slug']);
        $page->save();

        $this->assertSame('p2-concurrent-slug-2', $page->slug);
        $this->assertSame(2, DB::table('pages')->whereIn('slug', ['p2-concurrent-slug', 'p2-concurrent-slug-2'])->count());
    }

    public function test_slug_collision_retry_keeps_an_outer_transaction_usable(): void
    {
        $this->configureRacer();
        $this->raceNextCandidate();

        DB::transaction(function (): void {
            $page = new P2SlugRacePage(['title' => 'P2 outer transaction slug']);
            $page->save();

            DB::table('website_settings')->insert([
                'key' => 'p2_slug_outer_transaction',
                'value' => json_encode(['committed' => true], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertSame('p2-outer-transaction-slug-2', $page->slug);
        });

        $this->assertDatabaseHas('website_settings', ['key' => 'p2_slug_outer_transaction']);
        $this->assertSame(2, DB::table('pages')->whereIn('slug', ['p2-outer-transaction-slug', 'p2-outer-transaction-slug-2'])->count());
    }

    public function test_unrelated_postgresql_query_exception_is_not_treated_as_a_slug_collision(): void
    {
        try {
            Page::create([
                'title' => 'P2 unrelated database failure',
                'slug' => 'p2-unrelated-database-failure',
                'featured_media_id' => 9_999_999,
                'status' => 'draft',
            ]);
            $this->fail('Expected the foreign-key violation to propagate.');
        } catch (QueryException $exception) {
            $this->assertSame('23503', (string) ($exception->errorInfo[0] ?? $exception->getCode()));
        }
    }

    private function configureRacer(): void
    {
        config()->set('database.connections.p2_slug_racer', config('database.connections.pgsql'));
        DB::purge('p2_slug_racer');
    }

    private function raceNextCandidate(): void
    {
        P2SlugRacePage::$afterCandidate = function (string $candidate): void {
            DB::connection('p2_slug_racer')->table('pages')->insert([
                'title' => "Racer for {$candidate}",
                'slug' => $candidate,
                'status' => 'draft',
                'is_featured' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };
    }
}
