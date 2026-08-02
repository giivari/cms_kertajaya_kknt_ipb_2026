<?php

namespace App\Filament\Resources\NewsCategories\Tables;

use App\Filament\Exports\NewsCategoryExporter;
use App\Filament\Resources\NewsCategories\NewsCategoryResource;
use App\Filament\Support\AdminTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NewsCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-news-category-table')
            ->searchPlaceholder('Cari kategori berita...')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kategori')
                    ->description(fn ($record): ?string => $record->description)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('news_count')
                    ->label('Jumlah Berita')
                    ->counts('news')
                    ->badge()
                    ->sortable()
                    ->visibleFrom('md'),
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
                DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Kategori Berita')
                    ->modalDescription('Kategori hanya dapat dihapus jika belum digunakan oleh berita.')
                    ->modalSubmitActionLabel('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                AdminTable::exportAction(NewsCategoryExporter::class, NewsCategoryResource::class),
            ])
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateHeading('Belum ada kategori berita')
            ->emptyStateDescription('Kategori dapat dibuat dari form Berita atau halaman ini.');
    }
}
