<?php

namespace App\Services;

use App\Contracts\MediaUsageResolver;
use App\Models\Location;
use App\Models\Media;
use App\Models\WebsiteSetting;

class SettingsAndLocationMediaUsageResolver implements MediaUsageResolver
{
    // Media-ID controls currently persisted by WebsiteSettings. Link/page IDs
    // must not be mistaken for Media IDs merely because the numbers match.
    public function isInUse(Media $media): bool
    {
        return $this->getUsage($media) !== [];
    }

    public function getUsage(Media $media): array
    {
        $usages = Location::withTrashed()->where('media_id', $media->id)->pluck('name')
            ->map(fn (string $name): string => 'Location: '.$name)->all();

        // Read persisted values, not preview overlays or potentially stale cache.
        foreach (WebsiteSetting::whereIn('key', MediaReferenceCoordinator::SETTING_KEYS)->get() as $setting) {
            if ((is_int($setting->value) || is_string($setting->value)) &&
                (string) $setting->value === (string) $media->id) {
                $usages[] = 'Website setting: '.$setting->key;
            }
        }

        return $usages;
    }
}
