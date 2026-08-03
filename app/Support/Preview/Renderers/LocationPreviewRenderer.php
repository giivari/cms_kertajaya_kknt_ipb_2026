<?php

namespace App\Support\Preview\Renderers;

use App\Models\Location;
use App\Models\LocationCategory;
use App\Models\Media;
use App\Support\Preview\PreviewContext;
use Illuminate\Support\Facades\View;

class LocationPreviewRenderer
{
    public function render(PreviewContext $context): \Illuminate\Contracts\View\View
    {
        $snapshot = $context->recordSnapshot ?? [];
        $state = $context->normalizedState;

        $merged = array_merge($snapshot, $state);

        $location = new Location();
        $location->forceFill($merged);
        $location->id = $merged['id'] ?? null;

        $category = null;
        if (!empty($merged['location_category_id'])) {
            $category = LocationCategory::find($merged['location_category_id']);
        }
        $location->setRelation('category', $category);

        $media = null;
        if (!empty($merged['media_id'])) {
            $media = Media::find($merged['media_id']);
        }
        $location->setRelation('media', $media);

        // Map component expects float cast
        if (isset($location->latitude)) {
            $location->latitude = (float) $location->latitude;
        }
        if (isset($location->longitude)) {
            $location->longitude = (float) $location->longitude;
        }

        return View::make('public.map.show', [
            'location' => $location,
            'isPreview' => true,
        ]);
    }
}
