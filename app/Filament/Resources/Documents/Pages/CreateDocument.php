<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    use HasCreatePreview;

    protected static string $resource = DocumentResource::class;

    protected function previewType(): string
    {
        return 'document';
    }
}
