<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLocation extends ViewRecord
{
    protected static string $resource = LocationResource::class;

    public function getTitle(): string
    {
        return 'Lihat Lokasi';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah'),
            Action::make('website')->label('Lihat di Website')->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => route('public.map.show', $this->record))->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->isPublished()),
            DeleteAction::make()->label('Hapus'),
        ];
    }
}
