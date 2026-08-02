<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;

trait HasEditPreview
{
    abstract protected function previewType(): string;

    protected function getFormActions(): array
    {
        return [
            PreviewAction::make($this->previewType(), editing: true),
            $this->getSaveFormAction()->label('Simpan Perubahan'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}

