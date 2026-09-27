<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Services\DocumentFileAssignment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateDocument extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use \App\Filament\Support\Concerns\HasStatusActions;
    use \App\Filament\Support\Concerns\HasCreatePreview;
    protected static string $resource = DocumentResource::class;

    protected function previewType(): string
    {
        return 'document';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $data['file_media_id'] = app(DocumentFileAssignment::class)->assign($data);
            unset($data['document_upload'], $data['document_upload_name']);

            return parent::handleRecordCreation($data);
        });
    }
}
