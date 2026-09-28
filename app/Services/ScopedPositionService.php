<?php

namespace App\Services;

use App\Models\GalleryAlbum;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;

/**
 * Moves persisted relationship positions out of the final range before
 * Filament writes a reordered repeater one row at a time.
 */
final class ScopedPositionService
{
    public function reserveGalleryAlbumItems(GalleryAlbum $album): void
    {
        $items = $album->items()->lockForUpdate()->get(['id', 'position']);
        $count = $items->count();

        if ($count === 0) {
            return;
        }

        $offset = (int) $items->max('position') + $count + 1;
        $album->items()->update(['position' => DB::raw("position + {$offset}")]);
    }

    public function reserveMenuItemPositions(Menu $menu): void
    {
        $items = $menu->allItems()->lockForUpdate()->get(['id', 'position']);
        $count = $items->count();

        if ($count === 0) {
            return;
        }

        $offset = (int) $items->max('position') + $count + 1;
        $menu->allItems()->update(['position' => DB::raw("position + {$offset}")]);
    }
}
