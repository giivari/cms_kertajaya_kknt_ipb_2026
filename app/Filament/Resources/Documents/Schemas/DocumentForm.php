<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Konten Utama')
                            ->description('Tulis informasi dasar tentang dokumen publik ini.')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Judul Dokumen')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Penjelasan singkat mengenai isi dokumen...')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Klasifikasi Dokumen')
                            ->schema([
                                Select::make('document_category_id')
                                    ->label('Kategori Dokumen')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload(),
                                Hidden::make('status')
                                    ->default('draft'),
                            ]),
                        Section::make('Media Dokumen')
                            ->description('Unggah dokumen PDF dan gambar sampul jika ada.')
                            ->schema([
                                Select::make('file_media_id')
                                    ->label('Berkas Dokumen (PDF)')
                                    ->relationship('fileMedia', 'original_filename', fn (Builder $query) => $query->approved())
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Select::make('thumbnail_media_id')
                                    ->label('Gambar Sampul')
                                    ->relationship('thumbnailMedia', 'original_filename', fn (Builder $query) => $query->approved())
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Section::make('Statistik')
                            ->schema([
                                TextInput::make('download_count')
                                    ->label('Jumlah Unduhan')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
