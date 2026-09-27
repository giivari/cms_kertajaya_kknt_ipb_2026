<?php

namespace App\Filament\Resources\GalleryAlbums\Pages;

use App\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use App\Filament\Support\Concerns\HasEditPreview;
use App\Services\ScopedPositionService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditGalleryAlbum extends EditRecord
{
    use \App\Filament\Support\Concerns\HasStatusActions;
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

    protected function beforeSave(): void
    {
        // EditRecord keeps this hook and relationship persistence in the same
        // Filament transaction. Existing positions are first moved outside the
        // final range so a row-by-row repeater reorder cannot collide with the
        // unique (gallery_album_id, position) constraint.
        app(ScopedPositionService::class)->reserveGalleryAlbumItems($this->record);
    }
}
