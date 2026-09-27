<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;

trait HasEditPreview
{
    abstract protected function previewType(): string;



    protected function afterSave(): void
    {
        app(\App\Services\Preview\PreviewDraftStore::class)->forget(static::class, $this->record->id ?? null);
    }
}
