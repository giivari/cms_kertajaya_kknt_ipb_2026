<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('document_category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, \Filament\Forms\Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                \Filament\Forms\Components\Select::make('file_media_id')
                    ->relationship('fileMedia', 'filename', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->approved())
                    ->required()
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('thumbnail_media_id')
                    ->relationship('thumbnailMedia', 'filename', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->approved())
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->required()
                    ->default('published'),
                DateTimePicker::make('published_at')
                    ->default(now()),
                TextInput::make('download_count')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled(),
            ]);
    }
}
