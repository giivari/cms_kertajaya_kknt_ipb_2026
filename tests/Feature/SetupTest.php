<?php

use Illuminate\Support\Facades\DB;

test('admin path configuration is loaded', function () {
    expect(config('village.admin_path'))->toBe('test-admin-path');
});

test('generate installation id command works', function () {
    $this->artisan('village:install-id')
        ->assertExitCode(0);

    $env = file_get_contents(base_path('.env'));
    expect($env)->toContain('INSTALLATION_ID=VWCM-');
});

test('postgresql connection works', function () {
    $pdo = DB::connection()->getPdo();
    expect($pdo)->toBeInstanceOf(PDO::class);
});
