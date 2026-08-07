<?php

namespace App\Filament\Resources\GalleryAlbums\Pages;

use App\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateGalleryAlbum extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use \App\Filament\Support\Concerns\HasStatusActions;
    use HasCreatePreview;

    protected static string $resource = GalleryAlbumResource::class;

    public function getTitle(): string
    {
        return 'Buat Album Galeri';
    }

    public function getSubheading(): ?string
    {
        return 'Lengkapi informasi album, pilih foto terverifikasi, lalu tentukan status publikasinya.';
    }

    protected function previewType(): string
    {
        return 'gallery';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Album galeri berhasil dibuat';
    }
}
