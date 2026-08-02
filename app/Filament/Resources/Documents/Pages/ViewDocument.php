<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    public function getTitle(): string
    {
        return 'Lihat Dokumen';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah'),
            Action::make('website')->label('Lihat di Website')->url(fn () => route('documents.download', $this->record->slug))
                ->openUrlInNewTab()->visible(fn (): bool => $this->record->isPublished()),
            DeleteAction::make()->label('Hapus'),
        ];
    }
}
