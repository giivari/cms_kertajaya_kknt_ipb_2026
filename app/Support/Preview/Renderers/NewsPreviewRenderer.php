<?php

namespace App\Support\Preview\Renderers;

use App\Models\Media;
use App\Models\News;
use App\Models\NewsCategory;
use App\Support\Preview\PreviewContext;
use Illuminate\Support\Facades\View;

class NewsPreviewRenderer
{
    public function render(PreviewContext $context): \Illuminate\Contracts\View\View
    {
        $snapshot = $context->recordSnapshot ?? [];
        $state = $context->normalizedState;

        // Unsaved form state overrides the snapshot
        $merged = array_merge($snapshot, $state);

        $news = new News();
        // forceFill safely hydrates the model in-memory without persistence
        $news->forceFill($merged);

        // Set id if present in merged for potential route/key usages, but keep exists=false
        $news->id = $merged['id'] ?? null;

        // Manually resolve relations so the view can render them without lazy-loading
        $category = null;
        if (!empty($merged['news_category_id'])) {
            $category = NewsCategory::find($merged['news_category_id']);
        }
        $news->setRelation('category', $category);

        // Only persisted media is supported in Phase 3 for preview
        $media = null;
        if (!empty($merged['featured_media_id'])) {
            $media = Media::find($merged['featured_media_id']);
        }
        $news->setRelation('featuredMedia', $media);

        return View::make('public.news.show', [
            'newsItem' => $news,
            'isPreview' => true,
        ]);
    }
}
