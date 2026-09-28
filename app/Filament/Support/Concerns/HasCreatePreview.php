<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;

trait HasCreatePreview
{
    abstract protected function previewType(): string;



    protected function afterCreate(): void
    {
        app(\App\Services\Preview\PreviewDraftStore::class)->forget(static::class, null);
    }
}
