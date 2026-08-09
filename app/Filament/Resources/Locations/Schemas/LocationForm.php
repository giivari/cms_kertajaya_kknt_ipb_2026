<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\Hidden;
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
            \Filament\Schemas\Components\Group::make()
                ->schema([
                    \Filament\Schemas\Components\Section::make('Informasi Utama')
                        ->description('Tulis informasi dasar tentang lokasi.')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama Lokasi')
                                ->required()
                                ->placeholder('Contoh: Curug Sawer')
                                ->maxLength(255),
                            Textarea::make('short_description')
                                ->label('Deskripsi Singkat')
                                ->helperText('Opsional, maksimal 500 karakter.')
                                ->maxLength(500)
                                ->placeholder('Contoh: Air terjun indah dengan pemandangan alam yang asri...')
                                ->rows(3),
                            RichEditor::make('description')
                                ->label('Deskripsi Lengkap')
                                ->placeholder('Contoh: Curug Sawer terletak di kawasan Gunung Gede Pangrango, menawarkan...'),
                        ]),
                    \Filament\Schemas\Components\Section::make('Peta & Kordinat')
                        ->description('Detail koordinat dan alamat lengkap dari lokasi ini.')
                        ->schema([
                            Textarea::make('address')
                                ->label('Alamat Lengkap')
                                ->placeholder('Contoh: Jl. Situgunung Km. 4, Kadudampit')
                                ->rows(3)
                                ->columnSpanFull(),
                            TextInput::make('latitude')
                                ->label('Garis Lintang (Latitude)')
                                ->helperText('Contoh: -6.9876543')
                                ->placeholder('Contoh: -6.9876543')
                                ->required()
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90),
                            TextInput::make('longitude')
                                ->label('Garis Bujur (Longitude)')
                                ->helperText('Contoh: 106.1234567')
                                ->placeholder('Contoh: 106.1234567')
                                ->required()
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180),
                        ])
                        ->columns(['md' => 2]),
                ])
                ->extraAttributes(['class' => 'admin-form-main-column'])
                ->columnSpan(['lg' => 2]),

            \Filament\Schemas\Components\Group::make()
                ->schema([
                            Hidden::make('status')
                                ->default('draft'),
                    \Filament\Schemas\Components\Section::make('Klasifikasi')
                        ->description('Pengelompokan lokasi pada website.')
                        ->schema([
                            Select::make('location_category_id')
                                ->label('Kategori')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih kategori lokasi')
                                ->helperText('Buat Kategori Lokasi terlebih dahulu jika belum tersedia.'),
                            TextInput::make('sort_order')
                                ->label('Urutan Tampil')
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->helperText('Semakin kecil angka, semakin awal ditampilkan.'),
                        ]),
                    \Filament\Schemas\Components\Section::make('Gambar Utama')
                        ->description('Pilih gambar lokasi dari perpustakaan.')
                        ->schema([
                            Select::make('media_id')
                                ->label('Pilih Gambar')
                                ->relationship('media', 'original_filename', fn ($query) => $query->approvedImages())
                                ->searchable()
                                ->preload()
                                ->placeholder('Belum ada gambar dipilih'),
                        ]),
                ])
                ->extraAttributes(['class' => 'admin-form-side-column'])
                ->columnSpan(['lg' => 1]),
        ])
        ->columns(3)
        ->extraAttributes(['class' => 'admin-content-form']);
    }
}
