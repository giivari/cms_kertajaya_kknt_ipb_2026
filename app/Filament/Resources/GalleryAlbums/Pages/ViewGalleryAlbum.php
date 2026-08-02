<?php

namespace App\Filament\Resources\GalleryAlbums\Pages;

use App\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGalleryAlbum extends ViewRecord
{
    protected static string $resource = GalleryAlbumResource::class;

    public function getTitle(): string
    {
        return 'Lihat Galeri';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah'),
            Action::make('website')->label('Lihat di Website')->url(fn () => route('gallery.show', $this->record->slug))
                ->openUrlInNewTab()->visible(fn (): bool => $this->record->isPublished()),
            DeleteAction::make()->label('Hapus'),
        ];
    }
}
