<?php

use Illuminate\Support\Facades\DB;

test('admin path configuration is loaded', function () {
    expect(config('village.admin_path'))->toBe('test-admin-path');
});

test('generate installation id command works', function () {
    $envPath = base_path('.env');
    $envOriginallyExisted = file_exists($envPath);

    $originalEnv = $envOriginallyExisted
        ? file_get_contents($envPath)
        : null;

    if ($envOriginallyExisted && $originalEnv === false) {
        throw new RuntimeException('Unable to read the original .env file.');
    }

    try {
        $this->artisan('village:install-id')
            ->assertExitCode(0);

        $generatedEnv = file_get_contents($envPath);

        expect($generatedEnv)
            ->not->toBeFalse()
            ->toContain('INSTALLATION_ID=VWCM-');
    } finally {
        if ($envOriginallyExisted) {
            $restored = file_put_contents(
                $envPath,
                (string) $originalEnv
            );

            if ($restored === false) {
                throw new RuntimeException(
                    'Unable to restore the original .env file.'
                );
            }
        } elseif (file_exists($envPath)) {
            @unlink($envPath);
        }
    }
});

test('postgresql connection works', function () {
    $pdo = DB::connection()->getPdo();

    expect($pdo)->toBeInstanceOf(PDO::class);
});
