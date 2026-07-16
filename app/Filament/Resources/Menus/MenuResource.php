<?php

namespace App\Filament\Resources\Menus;

use App\Enums\LinkType;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-bars-3';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Site Structure';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Menu Settings')
                    ->schema([
                        TextInput::make('name')->required(),
                        TextInput::make('location')->required()->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description'),
                    ]),
                Forms\Components\Section::make('Menu Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                TextInput::make('label')->required(),
                                Select::make('link_type')
                                    ->options(LinkType::class)
                                    ->default(LinkType::CUSTOM->value)
                                    ->live()
                                    ->required(),
                                Select::make('page_id')
                                    ->relationship('page', 'title')
                                    ->searchable()
                                    ->visible(fn(Forms\Get $get) => $get('link_type') === LinkType::PAGE->value)
                                    ->required(fn(Forms\Get $get) => $get('link_type') === LinkType::PAGE->value),
                                TextInput::make('custom_url')
                                    ->url()
                                    ->visible(fn(Forms\Get $get) => $get('link_type') === LinkType::CUSTOM->value)
                                    ->required(fn(Forms\Get $get) => $get('link_type') === LinkType::CUSTOM->value),
                                Select::make('target')
                                    ->options(['_self' => 'Same Window', '_blank' => 'New Window'])
                                    ->default('_self'),
                                Toggle::make('is_visible')->default(true),
                                
                                Repeater::make('children')
                                    ->relationship('children')
                                    ->schema([
                                        TextInput::make('label')->required(),
                                        Select::make('link_type')
                                            ->options(LinkType::class)
                                            ->default(LinkType::CUSTOM->value)
                                            ->live()
                                            ->required(),
                                        Select::make('page_id')
                                            ->relationship('page', 'title')
                                            ->searchable()
                                            ->visible(fn(Forms\Get $get) => $get('link_type') === LinkType::PAGE->value)
                                            ->required(fn(Forms\Get $get) => $get('link_type') === LinkType::PAGE->value),
                                        TextInput::make('custom_url')
                                            ->url()
                                            ->visible(fn(Forms\Get $get) => $get('link_type') === LinkType::CUSTOM->value)
                                            ->required(fn(Forms\Get $get) => $get('link_type') === LinkType::CUSTOM->value),
                                        Select::make('target')
                                            ->options(['_self' => 'Same Window', '_blank' => 'New Window'])
                                            ->default('_self'),
                                        Toggle::make('is_visible')->default(true),
                                    ])
                                    ->orderColumn('position')
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['label'] ?? null),
                            ])
                            ->orderColumn('position')
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('location')->searchable(),
                TextColumn::make('description'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
