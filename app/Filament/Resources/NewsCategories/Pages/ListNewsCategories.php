<?php

namespace App\Filament\Resources\NewsCategories\Pages;

use App\Filament\Resources\NewsCategories\NewsCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNewsCategories extends ListRecords
{
    protected static string $resource = NewsCategoryResource::class;

    public function getTitle(): string
    {
        return 'Kategori Berita';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola pengelompokan berita. Halaman ini tidak ditampilkan pada menu utama admin.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Kategori')
                ->icon('heroicon-o-plus'),
        ];
    }
}
