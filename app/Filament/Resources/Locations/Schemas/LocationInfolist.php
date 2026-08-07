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
            \Filament\Schemas\Components\Group::make()->schema([
                \Filament\Schemas\Components\Section::make('Informasi Utama')->schema([
                    TextEntry::make('name')->label('Nama Lokasi'),
                    TextEntry::make('category.name')->label('Kategori'),
                    TextEntry::make('short_description')->label('Deskripsi Singkat')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('description')->label('Deskripsi Lengkap')->html()->placeholder('-')->columnSpanFull(),
                ])->columns(2),
                \Filament\Schemas\Components\Section::make('Peta & Koordinat')->schema([
                    TextEntry::make('address')->label('Alamat')->placeholder('-')->columnSpanFull(),
                    TextEntry::make('latitude')->label('Garis Lintang'),
                    TextEntry::make('longitude')->label('Garis Bujur'),
                ])->columns(2),
            ])->columnSpan(['lg' => 2]),
            \Filament\Schemas\Components\Group::make()->schema([
                \Filament\Schemas\Components\Section::make('Gambar Utama')->schema([
                    ImageEntry::make('media.url')->label('')->placeholder('Tidak ada gambar')->hiddenLabel(),
                ]),
                \Filament\Schemas\Components\Section::make('Status & Publikasi')->schema([
                    TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Terbit',
                        'archived' => 'Diarsipkan',
                        default => 'Draf',
                    })->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    }),
                    TextEntry::make('published_at')->label('Diterbitkan pada')
                        ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                        ->placeholder('-'),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }
}
