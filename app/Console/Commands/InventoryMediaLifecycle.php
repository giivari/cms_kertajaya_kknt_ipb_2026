<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class InventoryMediaLifecycle extends Command
{
    protected $signature = 'media:lifecycle-inventory {--json : Emit the read-only inventory as JSON}';

    protected $description = 'Read-only inventory of legacy public files and private media lifecycle artifacts';

    public function handle(): int
    {
        $inventory = [
            'legacy_active_public_derivatives' => [],
            'legacy_public_original_records' => [],
            'retired_public_derivatives' => [],
            'legacy_unclassified_public_files' => [],
            'private_unclassified_generations' => [],
            'stale_staging_files' => [],
            'cleanup_retry_media_ids' => [],
        ];
        $knownPrivate = [];
        $knownPublic = [];
        $activeAttempts = [];

        Media::withTrashed()->with('derivatives')->orderBy('id')->chunkById(100, function ($mediaItems) use (&$inventory, &$knownPrivate, &$knownPublic, &$activeAttempts): void {
            foreach ($mediaItems as $media) {
                if ($media->disk === 'public') {
                    $legacyOriginal = trim($media->directory, '/').'/'.$media->filename;
                    $inventory['legacy_public_original_records'][] = [
                        'media_id' => $media->id,
                        'path' => $legacyOriginal,
                    ];
                    $knownPublic['public:'.$legacyOriginal] = true;
                }
                if ($media->last_processing_status === 'processing' && $media->processing_token) {
                    $activeAttempts[(int) $media->id] = $media->processing_token;
                }
                if (in_array($media->cleanup_status, ['pending', 'failed'], true)) {
                    $inventory['cleanup_retry_media_ids'][] = $media->id;
                }
                foreach ($media->derivatives as $derivative) {
                    $key = $derivative->disk.':'.$derivative->filename;
                    if ($derivative->disk === 'public') {
                        $inventory['legacy_active_public_derivatives'][] = $derivative->filename;
                        $knownPublic[$key] = true;
                    } else {
                        $knownPrivate[$key] = true;
                    }
                }
                foreach (($media->metadata['retired_derivatives'] ?? []) as $retired) {
                    if (! is_array($retired) || ! is_string($retired['disk'] ?? null) || ! is_string($retired['filename'] ?? null)) {
                        continue;
                    }
                    if ($retired['disk'] === 'public') {
                        $inventory['retired_public_derivatives'][] = $retired['filename'];
                        $knownPublic['public:'.$retired['filename']] = true;
                    } else {
                        $knownPrivate[$retired['disk'].':'.$retired['filename']] = true;
                    }
                }
            }
        });

        foreach (Storage::disk('local')->allFiles('derivatives') as $filename) {
            if (! isset($knownPrivate['local:'.$filename])) {
                $inventory['private_unclassified_generations'][] = $filename;
            }
        }
        foreach (Storage::disk('public')->allFiles('media') as $filename) {
            if (! isset($knownPublic['public:'.$filename])) {
                $inventory['legacy_unclassified_public_files'][] = $filename;
            }
        }
        foreach (Storage::disk('local')->allFiles('staging') as $filename) {
            if (! preg_match('~^staging/(\d+)/([^/]+)/~', $filename, $matches)
                || ($activeAttempts[(int) $matches[1]] ?? null) !== $matches[2]) {
                $inventory['stale_staging_files'][] = $filename;
            }
        }

        foreach ($inventory as &$items) {
            $items = array_values(array_unique($items, SORT_REGULAR));
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->warn('READ-ONLY inventory; no file or database state was changed.');
            $this->table(['Classification', 'Count'], collect($inventory)->map(fn (array $items, string $key): array => [$key, count($items)])->values()->all());
            $this->line('Use --json to review repository-relative storage paths before planning a cutover.');
        }

        return self::SUCCESS;
    }
}
