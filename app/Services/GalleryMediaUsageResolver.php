<?php

namespace App\Services;

use App\Contracts\MediaUsageResolver;
use App\Models\Media;
use App\Models\GalleryAlbum;
use App\Models\GalleryAlbumItem;

class GalleryMediaUsageResolver implements MediaUsageResolver
{
    public function isInUse(Media $media): bool
    {
        return GalleryAlbum::where('cover_media_id', $media->id)->exists()
            || GalleryAlbumItem::where('media_id', $media->id)->exists();
    }

    public function getUsage(Media $media): array
    {
        $usages = [];
        
        $albums = GalleryAlbum::where('cover_media_id', $media->id)->get();
        foreach ($albums as $album) {
            $usages[] = "Gallery Cover: {$album->title}";
        }

        $items = GalleryAlbumItem::where('media_id', $media->id)->with('album')->get();
        foreach ($items as $item) {
            $albumTitle = $item->album ? $item->album->title : 'Unknown Album';
            $usages[] = "Gallery Item in: {$albumTitle}";
        }

        return $usages;
    }
}
