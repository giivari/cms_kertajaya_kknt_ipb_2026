<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    public static function generateUniqueSlug(?string $source, ?int $exceptId = null): string
    {
        $baseSlug = Str::slug((string) $source);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'item';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
