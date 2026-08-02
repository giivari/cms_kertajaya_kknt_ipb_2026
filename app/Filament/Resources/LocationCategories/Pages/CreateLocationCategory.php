<?php

namespace App\Filament\Resources\LocationCategories\Pages;

use App\Filament\Resources\LocationCategories\LocationCategoryResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateLocationCategory extends CreateRecord
{
    use HasCreatePreview;

    protected static string $resource = LocationCategoryResource::class;

    protected function previewType(): string
    {
        return 'location-category';
    }
}
