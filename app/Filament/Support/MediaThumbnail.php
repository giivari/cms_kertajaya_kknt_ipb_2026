<?php

namespace App\Filament\Support;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaThumbnail
{
    public static function path(?Media $media): ?string
    {
        if (! self::isEligibleImage($media)) {
            return null;
        }

        $derivative = $media->derivatives
            ->first(fn ($item): bool => (is_object($item->derivative_type) ? $item->derivative_type->value : $item->derivative_type) === 'public');

        if ((! $derivative) || (! str_starts_with((string) $derivative->mime_type, 'image/'))) {
            return null;
        }

        try {
            return Storage::disk($derivative->disk)->exists($derivative->filename)
                ? $derivative->filename
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function disk(?Media $media): string
    {
        $derivative = $media?->derivatives
            ->first(fn ($item): bool => (is_object($item->derivative_type) ? $item->derivative_type->value : $item->derivative_type) === 'public');

        return $derivative?->disk ?? (string) config('filament.default_filesystem_disk', 'public');
    }

    public static function placeholderUrl(?string $mimeType = null): string
    {
        $isPdf = $mimeType === 'application/pdf';
        $label = $isPdf ? 'Pratinjau dokumen tidak tersedia' : 'Gambar tidak tersedia';
        $icon = $isPdf
            ? '<path d="M16 11h11l7 7v19H16V11Zm11 2v7h7M20 27h10M20 31h10" fill="none" stroke="#60766F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            : '<path d="M14 16h20a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H14a2 2 0 0 1-2-2V18a2 2 0 0 1 2-2Zm0 17h20l-6-6-4.5 4.5-3-3L14 33Zm15-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" fill="#60766F"/>';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" role="img" aria-label="{$label}">
  <rect width="48" height="48" rx="8" fill="#E4ECE9"/>
  {$icon}
</svg>
SVG;

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    private static function isEligibleImage(?Media $media): bool
    {
        return $media
            && ($media->processing_status === MediaProcessingStatus::COMPLETED)
            && ($media->invisible_watermark_status === InvisibleWatermarkStatus::VERIFIED)
            && str_starts_with((string) $media->mime_type, 'image/');
    }
}
