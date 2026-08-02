<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPage extends ViewRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'Lihat Halaman';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah'),
            Action::make('website')
                ->label('Lihat di Website')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): string => route('pages.show', $this->record->slug))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->status === PageStatus::PUBLISHED
                    && $this->record->published_at?->lte(now())
                    && ! $this->record->trashed()),
            DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus Halaman')
                ->modalDescription('Halaman akan dihapus dan tautan publiknya tidak lagi dapat dibuka.')
                ->modalSubmitActionLabel('Hapus'),
        ];
    }
}
