<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Support\Concerns\HasEditPreview;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenu extends EditRecord
{
    use HasEditPreview;

    protected static string $resource = MenuResource::class;

    protected function previewType(): string
    {
        return 'menu';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            \App\Filament\Support\PreviewAction::make($this->previewType(), true),
            $this->getCancelFormAction(),
        ];
    }
}
