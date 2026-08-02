<?php

namespace App\Filament\Resources\DocumentCategories\Tables;

use App\Filament\Exports\DocumentCategoryExporter;
use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use App\Filament\Support\AdminTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-document-category-table')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label('Ubah'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                AdminTable::exportAction(DocumentCategoryExporter::class, DocumentCategoryResource::class),
            ]);
    }
}
