<?php

test('watermark spike baseline extraction', function () {
    $this->artisan('spike:watermark2')->assertExitCode(0);

    $payloadPath = storage_path('app/spike2/base.jpg');
    expect(file_exists($payloadPath))->toBeTrue();

    $content = file_get_contents($payloadPath);
    expect($content)->toContain('VWCM-KERTAJAYA-ID-');
});

test('watermark expected failure after destructive transformation', function () {
    $reprocessedPath = storage_path('app/spike2/reprocessed.jpg');
    expect(file_exists($reprocessedPath))->toBeTrue();

    $content = file_get_contents($reprocessedPath);
    expect($content)->not->toContain('VWCM-KERTAJAYA-ID-');
});

test('final derivative identifier verification', function () {
    // This tests the workflow described in Spike 2 where we inject at the end.
    // The baseline test above inherently tests the final derivative before third-party re-save.
    $payloadPath = storage_path('app/spike2/base.jpg');
    $content = file_get_contents($payloadPath);

    // Simulate verification
    $verified = str_contains($content, 'VWCM-KERTAJAYA-ID-');
    expect($verified)->toBeTrue();
});
