<?php

namespace App\Services;

use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MediaDeletionService
{
    public function __construct(protected MediaUsageService $usageService) {}

    /**
     * Validate that a single media item can be deleted.
     *
     * @throws Exception
     */
    public function validateDeletion(Media $media): void
    {
        if ($media->processing_status === MediaProcessingStatus::PENDING ||
            $media->processing_status === MediaProcessingStatus::PROCESSING) {
            throw new Exception('Cannot delete media that is currently pending or processing.');
        }

        if ($this->usageService->isInUse($media)) {
            throw new Exception('Cannot delete media because it is currently in use by other content.');
        }
    }

    /**
     * Validate and atomically delete multiple media records.
     * If any record is invalid, none are deleted.
     *
     * @param  Collection<int, Media>  $records
     * @return array{deleted: int, errors: string[]}
     */
    public function bulkDelete($records): array
    {
        $errors = [];

        // Phase 1: validate every record before deleting any
        foreach ($records as $record) {
            try {
                $this->validateDeletion($record);
            } catch (Exception $e) {
                $errors[] = "Media #{$record->id} ({$record->original_filename}): {$e->getMessage()}";
            }
        }

        if (count($errors) > 0) {
            return ['deleted' => 0, 'errors' => $errors];
        }

        // Phase 2: all valid — delete atomically inside a transaction
        $deleted = 0;
        DB::transaction(function () use ($records, &$deleted) {
            foreach ($records as $record) {
                $record->delete();
                $deleted++;
            }
        });

        return ['deleted' => $deleted, 'errors' => []];
    }
}
