<?php

namespace App\Filament\Resources\GalleryAlbums\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GalleryAlbumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                \Filament\Forms\Components\Select::make('cover_media_id')
                    ->relationship('coverMedia', 'filename')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->required()
                    ->default('published'),
                Toggle::make('is_featured')
                    ->required(),
                DateTimePicker::make('published_at')
                    ->default(now()),
                \Filament\Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        \Filament\Forms\Components\Select::make('media_id')
                            ->relationship('media', 'filename')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('caption'),
                        TextInput::make('alt_text'),
                    ])
                    ->orderColumn('position')
                    ->columnSpanFull()
                    ->defaultItems(0),
            ]);
    }
}
