<?php

namespace App\Filament\Resources\GalleryAlbums\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
                    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('cover_media_id')
                    ->relationship('coverMedia', 'filename', fn (Builder $query) => $query->approved())
                    ->searchable()
                    ->preload(),
                Select::make('status')
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
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('media_id')
                            ->relationship('media', 'filename', fn (Builder $query) => $query->approved())
                            ->required()
                            ->distinct()
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
