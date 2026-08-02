<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNews extends ViewRecord
{
    protected static string $resource = NewsResource::class;

    public function getTitle(): string
    {
        return 'Lihat Berita';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah'),
            Action::make('website')
                ->label('Lihat di Website')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => route('news.show', $this->record->slug))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->isPublished() && ! $this->record->trashed()),
            DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus Berita')
                ->modalDescription('Berita akan dihapus dan tidak lagi tampil di website.')
                ->modalSubmitActionLabel('Hapus'),
        ];
    }
}
