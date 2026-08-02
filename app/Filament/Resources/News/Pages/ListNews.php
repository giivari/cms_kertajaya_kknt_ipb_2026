<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNews extends ListRecords
{
    protected static string $resource = NewsResource::class;

    public function getTitle(): string
    {
        return 'Daftar Berita';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola publikasi berita, pengumuman, dan informasi terbaru desa.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Berita')
                ->icon('heroicon-o-plus'),
        ];
    }
}
