<?php

namespace App\Services;

use App\Enums\DerivativeType;
use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use App\Models\MediaDerivative;
use Illuminate\Support\Facades\Storage;

final class MediaDeliveryService
{
    /** @return array{path: string, mime: string, checksum: ?string}|null */
    public function resolve(Media $media, bool $allowArchived = false, DerivativeType $type = DerivativeType::PUBLIC): ?array
    {
        if ((! $allowArchived && $media->trashed())
            || $media->processing_status !== MediaProcessingStatus::COMPLETED
            || $media->invisible_watermark_status !== InvisibleWatermarkStatus::VERIFIED) {
            return null;
        }

        $derivative = $media->derivatives()
            ->where('derivative_type', $type)
            ->first();

        if (! $derivative || ! in_array($derivative->mime_type, MediaInputPolicy::PUBLIC_MIMES, true)) {
            return null;
        }

        $path = $this->containedPath($media, $derivative);
        if ($path === null) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if ($mime !== $derivative->mime_type || ! in_array($mime, MediaInputPolicy::PUBLIC_MIMES, true)) {
            return null;
        }

        if ($derivative->checksum && ! hash_equals($derivative->checksum, (string) hash_file('sha256', $path))) {
            return null;
        }

        return ['path' => $path, 'mime' => $mime, 'checksum' => $derivative->checksum];
    }

    public function url(?Media $media, DerivativeType $type = DerivativeType::PUBLIC): ?string
    {
        if (! $media || $media->trashed()
            || $media->processing_status !== MediaProcessingStatus::COMPLETED
            || $media->invisible_watermark_status !== InvisibleWatermarkStatus::VERIFIED
            || ! $media->derivatives->contains(fn (MediaDerivative $item): bool =>
                $item->derivative_type === $type
                && in_array($item->disk, ['local', 'public'], true)
                && in_array($item->mime_type, MediaInputPolicy::PUBLIC_MIMES, true)
            )) {
            return null;
        }

        return route('media.derivative', [
            'media' => $media->getKey(),
            ...($type === DerivativeType::THUMBNAIL ? ['variant' => 'thumbnail'] : []),
        ]);
    }

    private function containedPath(Media $media, MediaDerivative $derivative): ?string
    {
        if (! in_array($derivative->disk, ['local', 'public'], true)
            || str_contains($derivative->filename, "\0")
            || str_contains($derivative->filename, '\\')
            || str_starts_with($derivative->filename, '/')
            || preg_match('~(^|/)\.\.(/|$)~', $derivative->filename)) {
            return null;
        }

        $allowedPrefix = $derivative->disk === 'local'
            ? 'derivatives/'.$media->getKey().'/'
            : 'media/';
        if (! str_starts_with($derivative->filename, $allowedPrefix)) {
            return null;
        }

        $disk = Storage::disk($derivative->disk);
        $root = realpath($disk->path(''));
        $path = realpath($disk->path($derivative->filename));
        if ($root === false || $path === false || ! is_file($path) || ! is_readable($path) || is_link($path)) {
            return null;
        }

        $prefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $prefix) ? $path : null;
    }
}
