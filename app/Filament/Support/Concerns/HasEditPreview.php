<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;

trait HasEditPreview
{
    abstract protected function previewType(): string;



    protected function afterSave(): void
    {
        $cacheKey = 'preview_draft_' . static::class . '_' . ($this->record->id ?? 'new');
        if (session()->has($cacheKey)) {
            session()->forget($cacheKey);
        }
    }
}

