<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateLocation extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use \App\Filament\Support\Concerns\HasStatusActions;
    use HasCreatePreview;

    protected static string $resource = LocationResource::class;

    protected function previewType(): string
    {
        return 'location';
    }
}
