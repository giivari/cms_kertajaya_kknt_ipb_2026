<?php

namespace App\Filament\Resources\NewsCategories\Pages;

use App\Filament\Resources\NewsCategories\NewsCategoryResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsCategory extends CreateRecord
{
    use HasCreatePreview;

    protected static string $resource = NewsCategoryResource::class;

    public function getTitle(): string
    {
        return 'Buat Kategori Berita';
    }

    public function getSubheading(): ?string
    {
        return 'Tambahkan kategori yang dapat langsung dipilih pada form Berita.';
    }

    protected function previewType(): string
    {
        return 'news-category';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kategori berita berhasil dibuat';
    }
}
