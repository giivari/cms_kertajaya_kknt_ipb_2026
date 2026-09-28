<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class P6DatabaseBackendsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_actual_public_requests_persist_session_and_cache_on_disposable_postgresql(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('village_cms_test', config('database.connections.pgsql.database'));

        config(['session.driver' => 'database', 'cache.default' => 'database', 'queue.default' => 'database']);
        app('session')->forgetDrivers();
        Cache::forgetDriver('database');

        Cache::store('database')->put('p6_backend_marker', 'from-postgresql', 60);
        $this->assertDatabaseHas('cache', ['key' => config('cache.prefix').'p6_backend_marker']);
        $this->assertSame('from-postgresql', Cache::store('database')->get('p6_backend_marker'));

        $this->withSession(['p6_session_marker' => 'from-postgresql']);
        $this->get(route('home'))->assertOk();
        $this->assertGreaterThan(0, DB::table('sessions')->count());

        $this->get(route('home'))->assertOk();
        $this->assertSame('from-postgresql', session()->get('p6_session_marker'));
        $this->assertSame('database', config('queue.default'));
    }
}
