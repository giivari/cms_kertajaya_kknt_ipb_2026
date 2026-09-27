<?php

namespace App\Services;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;

final class AdminExportCleanupService
{
    public function pruneExpired(): int
    {
        $deleted = 0;

        Export::query()
            ->where('file_disk', 'admin_exports')
            ->where(function ($query): void {
                $query->where(fn ($query) => $query->where('lifecycle_state', 'completed')
                    ->where('completed_at', '<', now()->subDay()))
                    ->orWhere(fn ($query) => $query->where('lifecycle_state', 'failed')
                        ->where('updated_at', '<', now()->subDay()))
                    ->orWhere('lifecycle_state', 'cleanup_failed');
            })
            ->eachById(function (Export $candidate) use (&$deleted): void {
                DB::transaction(function () use ($candidate, &$deleted): void {
                    $export = Export::query()->whereKey($candidate->getKey())->lockForUpdate()->first();
                    if (! $export || $export->file_disk !== 'admin_exports'
                        || ! $this->isEligible($export)) {
                        return;
                    }

                    $disk = $export->getFileDisk();
                    $directory = $export->getFileDirectory();
                    $root = realpath($disk->path('filament_exports'));
                    $resolved = realpath($disk->path($directory));
                    if ($disk->directoryExists($directory)
                        && ($root === false || $resolved === false
                            || ! str_starts_with($resolved, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
                            || is_link($disk->path('filament_exports')) || is_link($disk->path($directory))
                            || $this->containsUnknownFiles($export))) {
                        $export->forceFill(['lifecycle_state' => 'cleanup_failed', 'cleanup_error' => 'unsafe_directory'])->save();
                        return;
                    }

                    try {
                        if ($disk->directoryExists($directory)
                            && (! $disk->deleteDirectory($directory) || $disk->directoryExists($directory))) {
                            throw new \RuntimeException('Direktori ekspor belum terhapus.');
                        }
                    } catch (\Throwable $exception) {
                        $export->forceFill(['lifecycle_state' => 'cleanup_failed', 'cleanup_error' => 'delete_failed'])->save();
                        report(new \RuntimeException('Cleanup ekspor gagal untuk ID '.$export->getKey().': '.get_class($exception)));
                        return;
                    }

                    $export->delete();
                    $deleted++;
                });
            });

        return $deleted;
    }

    private function containsUnknownFiles(Export $export): bool
    {
        $disk = $export->getFileDisk();
        $base = (string) $export->file_name;
        if (! preg_match('/^[A-Za-z0-9_-]+$/D', $base)) {
            return true;
        }
        foreach ($disk->allFiles($export->getFileDirectory()) as $path) {
            $name = basename($path);
            if (str_replace('\\', '/', $path) !== str_replace('\\', '/', $export->getFileDirectory()).'/'.$name) {
                return true;
            }
            if (! in_array($name, [
                'headers.csv', $base.'.csv', $base.'.xlsx', $base.'.pdf',
                $base.'.csv.pending', $base.'.xlsx.pending', $base.'.pdf.pending',
            ], true) && ! preg_match('/^[0-9]{16}\.csv$/D', $name)) {
                return true;
            }
            if (is_link($disk->path($path)) || is_link(dirname($disk->path($path)))) {
                return true;
            }
        }

        return false;
    }

    private function isEligible(Export $export): bool
    {
        if ($export->lifecycle_state === 'cleanup_failed') {
            return true;
        }
        if ($export->lifecycle_state === 'failed') {
            return $export->updated_at?->lt(now()->subDay()) ?? false;
        }

        return $export->lifecycle_state === 'completed'
            && $export->completed_at !== null
            && \Illuminate\Support\Carbon::createFromTimestamp((int) $export->completed_at)->lt(now()->subDay());
    }
}
