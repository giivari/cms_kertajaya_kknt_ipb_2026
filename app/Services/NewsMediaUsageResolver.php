<?php

namespace App\Services;

use App\Contracts\MediaUsageResolver;
use App\Models\Media;
use App\Models\News;

class NewsMediaUsageResolver implements MediaUsageResolver
{
    public function isInUse(Media $media): bool
    {
        return News::where('featured_media_id', $media->id)->exists();
    }

    public function getUsage(Media $media): array
    {
        $usages = [];
        $newsItems = News::where('featured_media_id', $media->id)->get();
        foreach ($newsItems as $news) {
            $usages[] = "News Featured Media: {$news->title}";
        }
        return $usages;
    }
}
