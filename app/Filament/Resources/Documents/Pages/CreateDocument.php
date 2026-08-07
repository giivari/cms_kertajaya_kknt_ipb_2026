<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use \App\Filament\Support\Concerns\HasStatusActions;
    protected static string $resource = DocumentResource::class;
}
