<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return 'Daftar Halaman';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola halaman informasi tetap yang ditampilkan pada website desa.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Halaman')
                ->icon('heroicon-o-plus'),
        ];
    }
}
