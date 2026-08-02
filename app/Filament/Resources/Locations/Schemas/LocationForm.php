<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),
            Select::make('location_category_id')
                ->label('Kategori')
                ->helperText('Buat Kategori Lokasi terlebih dahulu sebelum menambahkan lokasi.')
                ->relationship('category', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Textarea::make('address')->label('Alamat')->columnSpanFull(),
            TextInput::make('latitude')
                ->label('Garis Lintang')
                ->helperText('Koordinat utara atau selatan lokasi. Contoh: -6.9876543')
                ->required()->numeric()->minValue(-90)->maxValue(90),
            TextInput::make('longitude')
                ->label('Garis Bujur')
                ->helperText('Koordinat timur atau barat lokasi. Contoh: 106.1234567')
                ->required()->numeric()->minValue(-180)->maxValue(180),
            Textarea::make('short_description')->label('Deskripsi Singkat')->maxLength(500)->columnSpanFull(),
            RichEditor::make('description')->label('Deskripsi Lengkap')->columnSpanFull(),
            Select::make('media_id')
                ->label('Foto Utama')
                ->relationship('media', 'original_filename', fn (Builder $query) => $query->approvedImages())
                ->searchable()
                ->preload(),
            Select::make('status')
                ->options([
                    'draft' => 'Draf',
                    'published' => 'Terbit',
                    'archived' => 'Diarsipkan',
                ])
                ->required()
                ->default('draft'),
            TextInput::make('sort_order')->label('Urutan')->numeric()->default(0)->required(),
        ]);
    }
}
