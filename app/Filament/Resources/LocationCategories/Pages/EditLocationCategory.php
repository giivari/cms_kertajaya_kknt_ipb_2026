<?php

namespace App\Filament\Resources\LocationCategories\Pages;

use App\Filament\Resources\LocationCategories\LocationCategoryResource;
use App\Filament\Support\Concerns\HasEditPreview;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditLocationCategory extends EditRecord
{
    use HasEditPreview;

    protected static string $resource = LocationCategoryResource::class;

    protected function previewType(): string
    {
        return 'location-category';
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), ForceDeleteAction::make(), RestoreAction::make()];
    }
}
