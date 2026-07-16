<?php

namespace App\Contracts;

use App\Models\Media;

interface MediaUsageResolver
{
    public function isInUse(Media $media): bool;

    public function getUsage(Media $media): array;
}
