<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMedia extends ListRecords
{
    protected static string $resource = MediaResource::class;

    public function getTitle(): string
    {
        return 'Perpustakaan Media';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola gambar dan dokumen yang digunakan pada konten website desa.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Unggah Media')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
