<?php

namespace App\Filament\Resources\DocumentCategories\Pages;

use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentCategory extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use HasCreatePreview;

    protected static string $resource = DocumentCategoryResource::class;

    protected function previewType(): string
    {
        return 'document-category';
    }
}
