<?php

use Illuminate\Support\Facades\DB;

test('admin path configuration is loaded', function () {
    expect(config('village.admin_path'))->toBe('test-admin-path');
});

test('generate installation id command works', function () {
    $envPath = base_path('.env');
    $backupPath = base_path('.env.testing_backup');
    if (file_exists($envPath)) {
        copy($envPath, $backupPath);
    }

    $this->artisan('village:install-id')->assertExitCode(0);

    $env = file_get_contents($envPath);
    expect($env)->toContain('INSTALLATION_ID=VWCM-');

    if (file_exists($backupPath)) {
        rename($backupPath, $envPath);
    } else {
        @unlink($envPath);
    }
});

test('postgresql connection works', function () {
    $pdo = DB::connection()->getPdo();
    expect($pdo)->toBeInstanceOf(PDO::class);
});
