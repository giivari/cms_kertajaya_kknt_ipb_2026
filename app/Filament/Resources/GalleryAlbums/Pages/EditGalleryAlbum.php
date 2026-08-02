<?php

namespace App\Filament\Resources\GalleryAlbums\Pages;

use App\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use App\Filament\Support\Concerns\HasEditPreview;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryAlbum extends EditRecord
{
    use HasEditPreview;

    protected static string $resource = GalleryAlbumResource::class;

    public function getTitle(): string
    {
        return 'Ubah Album Galeri';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui informasi, urutan foto, atau status album tanpa mengubah alamat publiknya.';
    }

    protected function previewType(): string
    {
        return 'gallery';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Hapus'),
            ForceDeleteAction::make()->label('Hapus Permanen'),
            RestoreAction::make()->label('Pulihkan'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan album galeri berhasil disimpan';
    }
}
