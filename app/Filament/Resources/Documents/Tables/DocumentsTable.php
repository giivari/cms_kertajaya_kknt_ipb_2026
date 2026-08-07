<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Filament\Exports\DocumentExporter;
use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Support\AdminTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-document-table')
            ->recordUrl(fn ($record): string => DocumentResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Dokumen')
                    ->limit(40)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->placeholder('Tanpa kategori')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Terbit', 'archived' => 'Diarsipkan', default => 'Draf',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('download_count')
                    ->label('Jumlah Unduhan')
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('lg'),
                TextColumn::make('published_at')
                    ->label('Diterbitkan pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('deleted_at')
                    ->label('Dihapus pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draf',
                        'published' => 'Terbit',
                        'archived' => 'Diarsipkan',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('document_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make()->label('Status Penghapusan'),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make()->label('Lihat')->icon('heroicon-o-eye'),
                    EditAction::make()->label('Ubah')->icon('heroicon-o-pencil-square'),
                    \Filament\Tables\Actions\Action::make('archive')
                        ->label('Arsipkan')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->visible(fn ($record) => (is_object($record->status) ? $record->status->value : $record->status) === 'published')
                        ->action(fn ($record) => $record->update(['status' => 'archived']))
                        ->requiresConfirmation()
                        ->modalHeading('Arsipkan Dokumen')
                        ->modalDescription('Apakah Anda yakin ingin mengarsipkan dokumen ini?')
                        ->modalSubmitActionLabel('Arsipkan'),
                    \Filament\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Hapus Dokumen')
                        ->modalDescription('Dokumen akan dihapus dari pengelolaan dan tidak lagi tampil di website.')
                        ->modalSubmitActionLabel('Hapus'),
                    \Filament\Actions\RestoreAction::make()->label('Pulihkan')->icon('heroicon-o-arrow-path'),
                    \Filament\Actions\ForceDeleteAction::make()
                        ->label('Hapus Permanen')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Hapus Dokumen Secara Permanen')
                        ->modalDescription('Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Hapus Permanen'),
                ])
                    ->label('Aksi Dokumen')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi Dokumen'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                AdminTable::exportAction(DocumentExporter::class, DocumentResource::class),
            ])
            ->emptyStateIcon('heroicon-o-folder-open')
            ->emptyStateHeading('Belum ada dokumen')
            ->emptyStateDescription('Unggah dokumen pertama untuk membagikan informasi publik kepada warga.');
    }
}
