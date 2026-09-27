<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // All feature-test file mutations stay beneath disposable fake roots,
        // including legacy tests that did not fake the current local disk.
        foreach (['local', 'public', 'private', 'admin_exports'] as $disk) {
            Storage::fake($disk);
        }
    }
}
