<?php

namespace App\Services;

use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaDeletionService
{
    public function __construct(
        protected MediaUsageService $usageService,
        protected MediaReferenceCoordinator $references,
        protected MediaDeliveryService $delivery,
    ) {}

    /** @throws Exception */
    public function validateDeletion(Media $media): void
    {
        $media = Media::withTrashed()->findOrFail($media->getKey());
        if ($media->processing_status === MediaProcessingStatus::PENDING
            || $media->processing_status === MediaProcessingStatus::PROCESSING
            || $media->last_processing_status === 'processing') {
            throw new Exception('Cannot delete media that is currently pending or processing.');
        }
        if ($this->usageService->isInUse($media)) {
            throw new Exception('Cannot delete media because it is currently in use by other content.');
        }
    }

    public function archive(Media $media): bool
    {
        return DB::transaction(function () use ($media): bool {
            $current = $this->references->lockForDeletion((int) $media->getKey());
            $this->validateDeletion($current);
            if ($current->trashed()) {
                return true;
            }
            $current->processing_token = (string) Str::uuid();
            $current->saveQuietly();

            $deleted = (bool) $current->delete();
            if ($deleted) {
                \App\Services\AuditLogService::log('media_archived', $current);
            }

            return $deleted;
        });
    }

    public function restore(Media $media): bool
    {
        return DB::transaction(function () use ($media): bool {
            $current = $this->references->lockForDeletion((int) $media->getKey());
            if (! $current->trashed() || in_array($current->cleanup_status, ['pending', 'failed', 'completed'], true)) {
                return false;
            }
            if ($this->delivery->resolve($current, allowArchived: true) === null) {
                throw new Exception('Cannot restore media without a valid approved derivative.');
            }

            $restored = (bool) $current->restore();
            if ($restored) {
                \App\Services\AuditLogService::log('media_restored', $current);
            }

            return $restored;
        });
    }

    public function permanentlyDelete(Media $media): bool
    {
        DB::transaction(function () use ($media): void {
            $current = $this->references->lockForDeletion((int) $media->getKey());
            if (! $current->trashed()) {
                throw new Exception('Media must be archived before permanent deletion.');
            }
            $this->validateDeletion($current);
            $current->forceFill([
                'processing_token' => (string) Str::uuid(),
                'cleanup_status' => 'pending',
                'cleanup_error' => null,
                'cleanup_started_at' => now(),
            ])->saveQuietly();
        });

        try {
            $this->deleteOwnedFiles(Media::withTrashed()->with('derivatives')->findOrFail($media->getKey()));
        } catch (\Throwable $exception) {
            Media::withTrashed()->whereKey($media->getKey())->update([
                'cleanup_status' => 'failed',
                'cleanup_error' => 'tracked_file_cleanup_failed',
            ]);
            throw new Exception('Permanent deletion stopped because tracked-file cleanup failed.', previous: $exception);
        }

        return DB::transaction(function () use ($media): bool {
            $current = $this->references->lockForDeletion((int) $media->getKey());
            $this->validateDeletion($current);
            if ($current->cleanup_status !== 'pending') {
                throw new Exception('Media cleanup state changed before permanent deletion.');
            }
            $current->forceFill(['cleanup_status' => 'completed', 'cleanup_error' => null])->saveQuietly();

            $deleted = (bool) $current->forceDelete();
            if ($deleted) {
                \App\Services\AuditLogService::log('media_permanently_deleted', $current);
            }

            return $deleted;
        });
    }

    /** @param Collection<int, Media> $records
     *  @return array{deleted: int, errors: string[]}
     */
    public function bulkDelete($records): array
    {
        $errors = [];
        foreach ($records as $record) {
            try {
                $this->validateDeletion($record);
            } catch (Exception $exception) {
                $errors[] = "Media #{$record->id} ({$record->original_filename}): {$exception->getMessage()}";
            }
        }
        if ($errors !== []) {
            return ['deleted' => 0, 'errors' => $errors];
        }

        $deleted = 0;
        DB::transaction(function () use ($records, &$deleted): void {
            foreach ($records->sortBy('id') as $record) {
                if ($this->archive($record)) {
                    $deleted++;
                }
            }
        });

        return ['deleted' => $deleted, 'errors' => []];
    }

    private function deleteOwnedFiles(Media $media): void
    {
        $files = [];
        if ($media->disk === 'local' && $media->directory === 'originals') {
            $files[] = ['local', 'originals/'.$media->filename, 'original'];
            $pristine = $media->metadata['pristine_filename'] ?? null;
            if (is_string($pristine) && basename($pristine) === $pristine) {
                $files[] = ['local', 'originals/'.$pristine, 'original'];
            }
        } elseif ($media->disk === 'public') {
            $files[] = ['public', trim($media->directory, '/').'/'.$media->filename, 'legacy_original'];
        }
        foreach ($media->derivatives as $derivative) {
            $files[] = [$derivative->disk, $derivative->filename, 'derivative'];
        }
        foreach (($media->metadata['retired_derivatives'] ?? []) as $retired) {
            if (is_array($retired) && is_string($retired['disk'] ?? null) && is_string($retired['filename'] ?? null)) {
                $files[] = [$retired['disk'], $retired['filename'], 'derivative'];
            }
        }
        foreach (['staging/'.$media->id, 'derivatives/'.$media->id] as $directory) {
            foreach (Storage::disk('local')->allFiles($directory) as $filename) {
                $files[] = ['local', $filename, 'owned_generation'];
            }
        }

        foreach (collect($files)->unique(fn (array $file): string => $file[0].':'.$file[1]) as [$disk, $filename, $kind]) {
            $this->deleteContainedFile($media, $disk, $filename, $kind);
        }
    }

    private function deleteContainedFile(Media $media, string $diskName, string $filename, string $kind): void
    {
        if (! in_array($diskName, ['local', 'public'], true)
            || str_contains($filename, "\0") || str_contains($filename, '\\')
            || str_starts_with($filename, '/') || preg_match('~(^|/)\.\.(/|$)~', $filename)) {
            throw new Exception('Unsafe tracked media path.');
        }
        $allowed = match ($kind) {
            'original' => $diskName === 'local' && preg_match('~^originals/[^/]+$~D', $filename),
            'legacy_original' => $diskName === 'public' && (
                str_starts_with($filename, 'originals/') || str_starts_with($filename, 'media/')
            ),
            'owned_generation' => $diskName === 'local' && (str_starts_with($filename, 'staging/'.$media->id.'/') || str_starts_with($filename, 'derivatives/'.$media->id.'/')),
            default => ($diskName === 'local' && str_starts_with($filename, 'derivatives/'.$media->id.'/')) || ($diskName === 'public' && str_starts_with($filename, 'media/')),
        };
        if (! $allowed) {
            throw new Exception('Tracked media path is outside its owned namespace.');
        }

        $disk = Storage::disk($diskName);
        if (! $disk->exists($filename)) {
            return;
        }
        $root = realpath($disk->path(''));
        $path = realpath($disk->path($filename));
        if ($root === false || $path === false || is_link($path)
            || ! str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            || ! $disk->delete($filename) || $disk->exists($filename)) {
            throw new Exception('Tracked media file could not be removed safely.');
        }
    }
}
