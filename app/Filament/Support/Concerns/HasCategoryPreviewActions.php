<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;
use Filament\Resources\Pages\EditRecord;

trait HasCategoryPreviewActions
{
    protected function getFormActions(): array
    {
        return [
            $this instanceof EditRecord ? $this->getSaveFormAction() : $this->getCreateFormAction(),
            PreviewAction::make($this->previewType(), $this instanceof EditRecord),
            $this->getCancelFormAction(),
        ];
    }
}
