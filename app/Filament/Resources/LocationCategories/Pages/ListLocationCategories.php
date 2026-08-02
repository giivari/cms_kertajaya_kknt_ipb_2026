<?php

namespace App\Filament\Resources\LocationCategories\Pages;

use App\Filament\Resources\LocationCategories\LocationCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocationCategories extends ListRecords
{
    protected static string $resource = LocationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
