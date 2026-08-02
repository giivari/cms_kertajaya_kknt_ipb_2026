<?php

namespace App\Filament\Resources\LocationCategories;

use App\Filament\Resources\LocationCategories\Pages\CreateLocationCategory;
use App\Filament\Resources\LocationCategories\Pages\EditLocationCategory;
use App\Filament\Resources\LocationCategories\Pages\ListLocationCategories;
use App\Filament\Resources\LocationCategories\Schemas\LocationCategoryForm;
use App\Filament\Resources\LocationCategories\Tables\LocationCategoriesTable;
use App\Models\LocationCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationCategoryResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $model = LocationCategory::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string { return 'PENGATURAN'; }
    public static function getNavigationLabel(): string { return 'Kategori Lokasi'; }
    public static function getPluralModelLabel(): string { return 'Kategori Lokasi'; }
    public static function getModelLabel(): string { return 'Kategori Lokasi'; }
    public static function form(Schema $schema): Schema { return LocationCategoryForm::configure($schema); }
    public static function table(Table $table): Table { return LocationCategoriesTable::configure($table); }

    public static function getPages(): array
    {
        return [
            'index' => ListLocationCategories::route('/'),
            'create' => CreateLocationCategory::route('/create'),
            'edit' => EditLocationCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}

