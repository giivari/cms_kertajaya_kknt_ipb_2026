<?php

namespace App\Services;

use Filament\Actions\Exports\Models\Export;

final class AdminExportCleanupService
{
    public function pruneExpired(): int
    {
        $deleted = 0;

        Export::query()
            ->where('created_at', '<', now()->subDay())
            ->eachById(function (Export $export) use (&$deleted): void {
                $export->deleteFileDirectory();
                $export->delete();
                $deleted++;
            });

        return $deleted;
    }
}
