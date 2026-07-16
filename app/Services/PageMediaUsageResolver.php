<?php

namespace App\Services;

use App\Contracts\MediaUsageResolver;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageComponent;

class PageMediaUsageResolver implements MediaUsageResolver
{
    public function isInUse(Media $media): bool
    {
        return $this->getUsageCount($media) > 0;
    }

    public function getUsage(Media $media): array
    {
        $usages = [];

        // Check featured media on pages
        $pages = Page::where('featured_media_id', $media->id)->get();
        foreach ($pages as $page) {
            $usages[] = "Page Featured Media: {$page->title}";
        }

        // Check inside components (requires querying JSONB)
        $components = PageComponent::where('content_data->media_id', $media->id)
            ->orWhereJsonContains('content_data->images', (string) $media->id)
            ->orWhereJsonContains('content_data->images', $media->id) // Some might be int
            ->orWhereJsonContains('content_data->documents', (string) $media->id)
            ->orWhereJsonContains('content_data->documents', $media->id)
            ->with('section.page')
            ->get();

        foreach ($components as $component) {
            if ($component->section && $component->section->page) {
                $usages[] = "Page Component: {$component->section->page->title} ({$component->component_type})";
            }
        }

        return $usages;
    }

    protected function getUsageCount(Media $media): int
    {
        $pageCount = Page::where('featured_media_id', $media->id)->count();
        if ($pageCount > 0) {
            return $pageCount;
        }

        $componentCount = PageComponent::where('content_data->media_id', $media->id)
            ->orWhereJsonContains('content_data->images', (string) $media->id)
            ->orWhereJsonContains('content_data->images', $media->id)
            ->orWhereJsonContains('content_data->documents', (string) $media->id)
            ->orWhereJsonContains('content_data->documents', $media->id)
            ->count();

        return $pageCount + $componentCount;
    }
}
