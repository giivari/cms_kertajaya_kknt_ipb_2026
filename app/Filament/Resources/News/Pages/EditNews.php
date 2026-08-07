<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use App\Filament\Support\Concerns\HasEditPreview;
use App\Filament\Support\PreviewAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    use \App\Filament\Support\Concerns\HasStatusActions;
    use HasEditPreview;

    protected static string $resource = NewsResource::class;

    public function getTitle(): string
    {
        return 'Ubah Berita';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui isi berita tanpa mengubah alamat publik yang sudah digunakan.';
    }

    protected function previewType(): string
    {
        return 'news';
    }

    protected function getHeaderActions(): array
    {
        return [
            PreviewAction::make($this->previewType(), editing: true),
            DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus Berita')
                ->modalDescription('Berita akan dihapus dan tidak lagi tampil di website.')
                ->modalSubmitActionLabel('Hapus'),
            ForceDeleteAction::make()->label('Hapus Permanen'),
            RestoreAction::make()->label('Pulihkan'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan berita berhasil disimpan';
    }
}
