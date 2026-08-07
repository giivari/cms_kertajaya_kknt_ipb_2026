<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    use \App\Filament\Support\Concerns\HasStatusActions;
    use HasCreatePreview;

    protected static string $resource = NewsResource::class;

    public function getTitle(): string
    {
        return 'Buat Berita Baru';
    }

    public function getSubheading(): ?string
    {
        return 'Lengkapi konten utama, klasifikasi, dan status publikasi berita.';
    }

    protected function previewType(): string
    {
        return 'news';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Berita berhasil dibuat';
    }
}
