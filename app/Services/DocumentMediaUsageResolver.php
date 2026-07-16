<?php

namespace App\Services;

use App\Contracts\MediaUsageResolver;
use App\Models\Document;
use App\Models\Media;

class DocumentMediaUsageResolver implements MediaUsageResolver
{
    public function isInUse(Media $media): bool
    {
        return Document::where('file_media_id', $media->id)
            ->orWhere('thumbnail_media_id', $media->id)
            ->exists();
    }

    public function getUsage(Media $media): array
    {
        $usages = [];

        $documents = Document::where('file_media_id', $media->id)
            ->orWhere('thumbnail_media_id', $media->id)
            ->get();

        foreach ($documents as $document) {
            if ($document->file_media_id === $media->id) {
                $usages[] = "Document File: {$document->title}";
            }
            if ($document->thumbnail_media_id === $media->id) {
                $usages[] = "Document Thumbnail: {$document->title}";
            }
        }

        return $usages;
    }
}
