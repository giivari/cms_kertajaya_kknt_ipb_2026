<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    use HasCreatePreview;

    protected static string $resource = LocationResource::class;

    protected function previewType(): string
    {
        return 'location';
    }
}
