<?php

namespace App\Support\Preview\Renderers;

use App\Models\GalleryAlbum;
use App\Models\Media;
use App\Support\Preview\PreviewContext;
use Illuminate\Support\Facades\View;

class GalleryAlbumPreviewRenderer
{
    public function render(PreviewContext $context): \Illuminate\Contracts\View\View
    {
        $snapshot = $context->recordSnapshot ?? [];
        $state = $context->normalizedState;

        $merged = array_merge($snapshot, $state);

        $album = new GalleryAlbum();
        $albumData = $merged;
        unset($albumData['items']);
        $album->forceFill($albumData);
        $album->id = $merged['id'] ?? null;

        $coverMedia = null;
        if (!empty($merged['cover_media_id'])) {
            $coverMedia = Media::find($merged['cover_media_id']);
        }
        $album->setRelation('coverMedia', $coverMedia);

        $albumItems = collect();
        if (isset($merged['items']) && is_array($merged['items'])) {
            foreach ($merged['items'] as $itemData) {
                $item = new \App\Models\GalleryAlbumItem();
                $item->forceFill([
                    'caption' => $itemData['caption'] ?? null,
                    'alt_text' => $itemData['alt_text'] ?? null,
                ]);
                if (!empty($itemData['media_id'])) {
                    $media = Media::find($itemData['media_id']);
                    if ($media) {
                        $item->setRelation('media', $media);
                    }
                }
                $albumItems->push($item);
            }
        }
        $album->setRelation('items', $albumItems);

        return View::make('public.gallery.show', [
            'album' => $album,
            'isPreview' => true,
        ]);
    }
}
