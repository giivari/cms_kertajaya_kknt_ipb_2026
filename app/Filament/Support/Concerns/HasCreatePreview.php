<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;

trait HasCreatePreview
{
    abstract protected function previewType(): string;



    protected function afterCreate(): void
    {
        $cacheKey = 'preview_draft_' . static::class . '_new';
        if (session()->has($cacheKey)) {
            session()->forget($cacheKey);
        }
    }
}

