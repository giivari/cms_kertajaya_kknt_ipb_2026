<?php

use App\Services\AdminExportCleanupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn (): int => app(AdminExportCleanupService::class)->pruneExpired())
    ->hourly()
    ->name('admin-exports:prune')
    ->withoutOverlapping();
