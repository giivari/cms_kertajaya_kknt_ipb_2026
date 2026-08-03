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
        $album->forceFill($merged);
        $album->id = $merged['id'] ?? null;

        $coverMedia = null;
        if (!empty($merged['cover_media_id'])) {
            $coverMedia = Media::find($merged['cover_media_id']);
        }
        $album->setRelation('coverMedia', $coverMedia);

        $mediaItems = collect();
        if (isset($merged['gallery_media']) && is_array($merged['gallery_media'])) {
            foreach ($merged['gallery_media'] as $itemData) {
                if (!empty($itemData['media_id'])) {
                    $media = Media::find($itemData['media_id']);
                    if ($media) {
                        $mediaItems->push($media);
                    }
                }
            }
        }
        $album->setRelation('galleryMedia', $mediaItems); // Assuming relations are set this way or we mock it.

        return View::make('public.gallery.show', [
            'album' => $album,
            'mediaItems' => $mediaItems,
            'isPreview' => true,
        ]);
    }
}
