<?php

namespace App\Filament\Resources\LocationCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LocationCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(150),
            Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
            TextInput::make('icon')->label('Ikon')->maxLength(100)->helperText('Nama ikon opsional untuk identitas kategori.'),
            TextInput::make('sort_order')->label('Urutan')->numeric()->default(0)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true)->required(),
        ]);
    }
}
