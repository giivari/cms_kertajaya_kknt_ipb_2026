<?php

namespace App\Services;

use App\Enums\MediaProcessingStatus;
use App\Jobs\ProcessMediaJob;
use App\Models\Media;
use Illuminate\Http\UploadedFile;

class MediaProcessingService
{
    public function handleUpload(UploadedFile $file, array $metadata = []): Media
    {
        // Validation is usually done in Requests, but we ensure safe filenames and store privately
        $filename = md5(uniqid()).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('originals', $filename, 'private');

        $media = Media::create([
            'original_filename' => $metadata['original_filename'] ?? $file->getClientOriginalName(),
            'filename' => $filename,
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension() ?: $file->guessExtension() ?: '',
            'directory' => 'media', // base directory
            'size' => $file->getSize(),
            'disk' => 'private',
            'width' => $metadata['width'] ?? null,
            'height' => $metadata['height'] ?? null,
            'alt_text' => $metadata['alt_text'] ?? null,
            'caption' => $metadata['caption'] ?? null,
            'processing_status' => MediaProcessingStatus::PENDING,
        ]);

        // Dispatch processing job
        ProcessMediaJob::dispatchSync($media);

        return $media;
    }
}
