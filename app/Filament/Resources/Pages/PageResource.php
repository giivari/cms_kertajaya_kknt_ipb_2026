<?php

namespace App\Filament\Resources\Pages;

use App\Enums\PageStatus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Pages';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Page Information')
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Page::class, 'slug', ignoreRecord: true),
                                Forms\Components\Textarea::make('excerpt')
                                    ->rows(3),
                            ]),

                        Section::make('Page Builder')
                            ->schema([
                                Repeater::make('builder_sections')
                                    ->label('Sections')
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Section')
                                    ->schema([
                                        Hidden::make('id'),
                                        TextInput::make('name')->label('Section Name')->required(),
                                        Select::make('layout_type')
                                            ->options([
                                                'single_column' => 'Single Column',
                                                'two_columns' => 'Two Columns',
                                                'three_columns' => 'Three Columns',
                                                'hero' => 'Hero',
                                                'full_width' => 'Full Width',
                                            ])
                                            ->default('single_column')
                                            ->required(),
                                        Forms\Components\Toggle::make('is_visible')->default(true)->label('Visible'),
                                        // Component Builder inside the section
                                        Builder::make('components')
                                            ->blocks([
                                                Block::make('heading')
                                                    ->schema([
                                                        TextInput::make('text')->required(),
                                                        Select::make('level')->options(['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4'])->default('h2')->required(),
                                                        Select::make('alignment')->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])->default('left'),
                                                    ]),
                                                Block::make('rich_text')
                                                    ->schema([
                                                        Forms\Components\RichEditor::make('content')->required(),
                                                    ]),
                                                Block::make('image')
                                                    ->schema([
                                                        Select::make('media_id')
                                                            ->relationship('featuredMedia', 'original_filename', fn(EloquentBuilder $query) => $query->where('mime_type', 'like', 'image/%'))
                                                            ->searchable()
                                                            ->required(),
                                                        TextInput::make('caption'),
                                                        TextInput::make('alt_text'),
                                                    ]),
                                                Block::make('gallery')
                                                    ->schema([
                                                        Select::make('images')
                                                            ->multiple()
                                                            ->relationship('featuredMedia', 'original_filename', fn(EloquentBuilder $query) => $query->where('mime_type', 'like', 'image/%'))
                                                            ->searchable(),
                                                    ]),
                                                Block::make('statistics')
                                                    ->schema([
                                                        Repeater::make('items')
                                                            ->schema([
                                                                TextInput::make('label')->required(),
                                                                TextInput::make('value')->required(),
                                                                TextInput::make('icon'),
                                                            ]),
                                                    ]),
                                                Block::make('video')
                                                    ->schema([
                                                        TextInput::make('video_url')->url()->required(),
                                                        TextInput::make('caption'),
                                                    ]),
                                                Block::make('map')
                                                    ->schema([
                                                        TextInput::make('latitude')->numeric()->required(),
                                                        TextInput::make('longitude')->numeric()->required(),
                                                        TextInput::make('zoom')->numeric()->default(15),
                                                    ]),
                                                Block::make('documents')
                                                    ->schema([
                                                        Select::make('documents')
                                                            ->multiple()
                                                            ->relationship('featuredMedia', 'original_filename', fn(EloquentBuilder $query) => $query->where('mime_type', 'application/pdf'))
                                                            ->searchable(),
                                                    ]),
                                                Block::make('cta_button')
                                                    ->schema([
                                                        TextInput::make('text')->required(),
                                                        TextInput::make('url')->required(),
                                                        Select::make('style')->options(['primary' => 'Primary', 'secondary' => 'Secondary', 'outline' => 'Outline'])->default('primary'),
                                                    ]),
                                                Block::make('card_grid')
                                                    ->schema([
                                                        Repeater::make('cards')
                                                            ->schema([
                                                                TextInput::make('title')->required(),
                                                                Forms\Components\Textarea::make('description'),
                                                                TextInput::make('link_url'),
                                                            ]),
                                                    ]),
                                                Block::make('contact_block')
                                                    ->schema([
                                                        TextInput::make('email')->email(),
                                                        TextInput::make('phone'),
                                                        Forms\Components\Textarea::make('address'),
                                                    ]),
                                            ])
                                            ->collapsed(),
                                    ])
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Publishing')
                            ->schema([
                                Select::make('status')
                                    ->options(PageStatus::class)
                                    ->default(PageStatus::DRAFT->value)
                                    ->required(),
                                Forms\Components\DateTimePicker::make('published_at'),
                            ]),
                        Section::make('SEO')
                            ->schema([
                                TextInput::make('seo_title'),
                                Forms\Components\Textarea::make('seo_description')->rows(3),
                            ]),
                        Section::make('Media')
                            ->schema([
                                Select::make('featured_media_id')
                                    ->relationship('featuredMedia', 'original_filename', fn(EloquentBuilder $query) => $query->where('mime_type', 'like', 'image/%'))
                                    ->searchable(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(PageStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
