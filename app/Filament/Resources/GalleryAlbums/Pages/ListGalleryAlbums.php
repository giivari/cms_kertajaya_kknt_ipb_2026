<?php

namespace App\Filament\Resources\GalleryAlbums\Pages;

use App\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGalleryAlbums extends ListRecords
{
    protected static string $resource = GalleryAlbumResource::class;

    public function getTitle(): string
    {
        return 'Album Galeri';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola album foto untuk mendokumentasikan kegiatan dan potensi desa.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Album')
                ->icon('heroicon-o-plus'),
        ];
    }
}
