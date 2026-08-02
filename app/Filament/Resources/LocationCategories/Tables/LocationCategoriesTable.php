<?php

namespace App\Filament\Resources\LocationCategories\Tables;

use App\Filament\Exports\LocationCategoryExporter;
use App\Filament\Resources\LocationCategories\LocationCategoryResource;
use App\Filament\Support\AdminTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LocationCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-location-category-table')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('locations_count')->label('Lokasi')->counts('locations')->visibleFrom('md'),
                TextColumn::make('sort_order')->label('Urutan')->sortable()->visibleFrom('lg'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('updated_at')->label('Terakhir Diperbarui')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')->sortable()->visibleFrom('md'),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make()->label('Ubah')])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                AdminTable::exportAction(LocationCategoryExporter::class, LocationCategoryResource::class),
            ]);
    }
}
