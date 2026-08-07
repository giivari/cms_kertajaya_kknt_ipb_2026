<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateMenu extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use HasCreatePreview;

    protected static string $resource = MenuResource::class;

    protected function previewType(): string
    {
        return 'menu';
    }
}
