<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Services\DocumentFileAssignment;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditDocument extends EditRecord
{
    use \App\Filament\Support\Concerns\HasStatusActions;
    use \App\Filament\Support\Concerns\HasEditPreview;
    protected static string $resource = DocumentResource::class;

    protected function previewType(): string
    {
        return 'document';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $data['file_media_id'] = app(DocumentFileAssignment::class)->assign($data, $record->file_media_id);
            unset($data['document_upload'], $data['document_upload_name']);

            return parent::handleRecordUpdate($record, $data);
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
