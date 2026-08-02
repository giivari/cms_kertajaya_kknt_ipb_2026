<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Dokumen')->schema([
                TextEntry::make('title')->label('Judul'),
                TextEntry::make('category.name')->label('Kategori')->placeholder('-'),
                TextEntry::make('description')->label('Deskripsi')->placeholder('-')->columnSpanFull(),
                TextEntry::make('fileMedia.original_filename')->label('Berkas Dokumen'),
                TextEntry::make('download_count')->label('Jumlah Unduhan')->numeric(),
                TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'published' => 'Terbit', 'archived' => 'Diarsipkan', default => 'Draf',
                }),
                TextEntry::make('published_at')->label('Diterbitkan pada')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')->placeholder('-'),
            ])->columns(2),
        ]);
    }
}
