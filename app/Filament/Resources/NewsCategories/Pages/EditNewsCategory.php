<?php

namespace App\Filament\Resources\NewsCategories\Pages;

use App\Filament\Resources\NewsCategories\NewsCategoryResource;
use App\Filament\Support\Concerns\HasEditPreview;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNewsCategory extends EditRecord
{
    use HasEditPreview;

    protected static string $resource = NewsCategoryResource::class;

    public function getTitle(): string
    {
        return 'Ubah Kategori Berita';
    }

    public function getSubheading(): ?string
    {
        return 'Perubahan nama kategori tidak mengubah alamat teknis yang sudah tersimpan.';
    }

    protected function previewType(): string
    {
        return 'news-category';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus Kategori Berita')
                ->modalDescription('Kategori hanya dapat dihapus jika belum digunakan oleh berita.')
                ->modalSubmitActionLabel('Hapus'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan kategori berhasil disimpan';
    }
}
