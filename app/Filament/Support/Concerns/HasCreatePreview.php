<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;

trait HasCreatePreview
{
    abstract protected function previewType(): string;

    protected function getFormActions(): array
    {
        return [
            PreviewAction::make($this->previewType()),
            $this->getCreateFormAction()->label('Simpan'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}

