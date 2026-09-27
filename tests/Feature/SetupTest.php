<?php

use Illuminate\Support\Facades\DB;

test('admin path configuration is loaded', function () {
    expect(config('village.admin_path'))->toBe('test-admin-path');
});

test('generate installation id command works', function () {
    $originalBasePath = base_path();
    $sandbox = storage_path('framework/testing/install-id-'.bin2hex(random_bytes(8)));
    if (! mkdir($sandbox, 0700, true) && ! is_dir($sandbox)) {
        throw new RuntimeException('Unable to create isolated installation-ID fixture.');
    }
    $envPath = $sandbox.DIRECTORY_SEPARATOR.'.env';
    file_put_contents($envPath, "APP_ENV=testing\nINSTALLATION_ID=fixture\n");

    try {
        app()->setBasePath($sandbox);
        $this->artisan('village:install-id')
            ->assertExitCode(0);

        $generatedEnv = file_get_contents($envPath);

        expect($generatedEnv)
            ->not->toBeFalse()
            ->toMatch('/INSTALLATION_ID=VWCM-[A-Z0-9]+-[0-9]{3}/');
    } finally {
        app()->setBasePath($originalBasePath);
        unlink($envPath);
        rmdir($sandbox);
    }
});

test('postgresql connection works', function () {
    $pdo = DB::connection()->getPdo();

    expect($pdo)->toBeInstanceOf(PDO::class);
});
