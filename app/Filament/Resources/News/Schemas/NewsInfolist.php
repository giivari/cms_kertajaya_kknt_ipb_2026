<?php

namespace App\Filament\Resources\News\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Berita')->schema([
                TextEntry::make('title')->label('Judul'),
                TextEntry::make('category.name')->label('Kategori')->placeholder('-'),
                TextEntry::make('excerpt')->label('Ringkasan')->placeholder('-')->columnSpanFull(),
                TextEntry::make('content')->label('Isi Berita')->html()->columnSpanFull(),
                TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'published' => 'Terbit', 'archived' => 'Diarsipkan', default => 'Draf',
                }),
                TextEntry::make('published_at')->label('Diterbitkan pada')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')->placeholder('-'),
            ])->columns(2),
        ]);
    }
}
