<?php

namespace App\Filament\Resources\NewsCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->description('Kategori membantu pengunjung menemukan berita dengan topik yang sama.')
                    ->schema(static::fields())
                    ->columnSpanFull(),
            ])
            ->extraAttributes(['class' => 'admin-content-form']);
    }

    public static function fields(): array
    {
        return [
            TextInput::make('name')
                ->label('Nama Kategori')
                ->placeholder('Contoh: Kegiatan Warga')
                ->required()
                ->maxLength(150)
                ->unique(\App\Models\NewsCategory::class, 'name', ignoreRecord: true),
            Textarea::make('description')
                ->label('Deskripsi')
                ->placeholder('Jelaskan jenis berita yang termasuk dalam kategori ini.')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }
}
