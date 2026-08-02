<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Lokasi')->schema([
                TextEntry::make('name')->label('Nama'),
                TextEntry::make('category.name')->label('Kategori Lokasi'),
                TextEntry::make('address')->label('Alamat')->placeholder('-'),
                TextEntry::make('latitude')->label('Garis Lintang'),
                TextEntry::make('longitude')->label('Garis Bujur'),
                TextEntry::make('short_description')->label('Deskripsi Singkat')->placeholder('-')->columnSpanFull(),
                TextEntry::make('description')->label('Deskripsi Lengkap')->html()->placeholder('-')->columnSpanFull(),
                ImageEntry::make('media.url')->label('Foto Utama')->placeholder('-'),
                TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'published' => 'Terbit',
                    'archived' => 'Diarsipkan',
                    default => 'Draf',
                }),
                TextEntry::make('published_at')->label('Diterbitkan pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->placeholder('-'),
            ])->columns(2),
        ]);
    }
}
