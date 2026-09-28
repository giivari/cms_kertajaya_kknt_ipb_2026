<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Resolves document source bytes without using the image-watermark derivative contract. */
final class DocumentDeliveryService
{
    public function resolve(Document $document, bool $public = true): ?array
    {
        if ($document->trashed() || ($public && ! $document->isPublished())) {
            return null;
        }

        return $this->resolveMedia($document->fileMedia);
    }

    public function resolveMedia(?Media $media): ?array
    {
        if (! $media || $media->trashed() || in_array($media->cleanup_status, ['pending', 'failed', 'completed'], true)) {
            return null;
        }

        $path = $this->containedSource($media);
        if ($path === null) {
            return null;
        }

        try {
            // Media labels may be edited independently of the stored file type.
            $file = app(DocumentFilePolicy::class)->inspect($path, 'document.'.$media->extension);
        } catch (ValidationException) {
            return null;
        }

        if ($file['mime'] !== $media->mime_type || $file['extension'] !== strtolower((string) $media->extension)
            || (int) $media->size !== $file['size']) {
            return null;
        }

        // New documents require a trusted checksum. Historical files can still be
        // structurally checked, but never gain a fabricated verification status.
        if (($media->metadata['document_validation']['version'] ?? null) === 1 && ! $media->checksum) {
            return null;
        }
        if ($media->checksum && ! hash_equals($media->checksum, $file['checksum'])) {
            return null;
        }

        return ['path' => $path, 'mime' => $file['mime'], 'extension' => $file['extension']];
    }

    private function containedSource(Media $media): ?string
    {
        $directory = trim((string) $media->directory, '/');
        if (! in_array($media->disk, ['local', 'public'], true)
            || ! in_array($directory, ['originals', 'media'], true)
            || $media->filename !== basename($media->filename)
            || str_contains($media->filename, DIRECTORY_SEPARATOR) || str_contains($media->filename, "\0")
            || in_array($media->filename, ['', '.', '..'], true)
            || ($media->disk === 'local' && $directory !== 'originals')) {
            return null;
        }

        $disk = Storage::disk($media->disk);
        $root = realpath($disk->path(''));
        $candidate = $disk->path($directory.'/'.$media->filename);
        $path = realpath($candidate);
        if ($root === false || $path === false || is_link($candidate) || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) ? $path : null;
    }
}
