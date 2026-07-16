<?php

namespace App\Services;

use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use Exception;

class MediaDeletionService
{
    public function __construct(protected MediaUsageService $usageService) {}

    /**
     * Checks if a media item can be deleted.
     * Throws an exception if it cannot be deleted.
     *
     * @throws Exception
     */
    public function validateDeletion(Media $media): void
    {
        // 1. Reject if pending or processing
        if ($media->processing_status === MediaProcessingStatus::PENDING ||
            $media->processing_status === MediaProcessingStatus::PROCESSING) {
            throw new Exception('Cannot delete media that is currently pending or processing.');
        }

        // 2. Reject if any MediaUsageResolver reports usage
        if ($this->usageService->isInUse($media)) {
            throw new Exception('Cannot delete media because it is currently in use by other content.');
        }

        // 3. (Future) deletion would violate protected relationship can be handled by resolvers.
    }
}
