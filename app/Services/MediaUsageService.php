<?php

namespace App\Services;

use App\Contracts\MediaUsageResolver;
use App\Models\Media;

class MediaUsageService
{
    protected array $resolvers = [];

    public function registerResolver(MediaUsageResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function isInUse(Media $media): bool
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->isInUse($media)) {
                return true;
            }
        }

        return false;
    }

    public function getUsageInfo(Media $media): array
    {
        $info = [];
        foreach ($this->resolvers as $resolver) {
            $info = array_merge($info, $resolver->getUsage($media));
        }

        return $info;
    }
}
