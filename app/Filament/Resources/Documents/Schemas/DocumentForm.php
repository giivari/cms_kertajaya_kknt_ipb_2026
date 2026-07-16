<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('file_media_id')
                    ->relationship('fileMedia', 'filename', fn (Builder $query) => $query->approved())
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('thumbnail_media_id')
                    ->relationship('thumbnailMedia', 'filename', fn (Builder $query) => $query->approved())
                    ->searchable()
                    ->preload(),
                Select::make('status')
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
