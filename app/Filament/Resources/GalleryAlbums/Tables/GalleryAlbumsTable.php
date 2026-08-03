<?php

namespace App\Filament\Resources\GalleryAlbums\Tables;

use App\Filament\Exports\GalleryAlbumExporter;
use App\Filament\Resources\GalleryAlbums\GalleryAlbumResource;
use App\Filament\Support\AdminTable;
use App\Filament\Support\MediaThumbnail;
use App\Models\GalleryAlbum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GalleryAlbumsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-gallery-table')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['coverMedia.derivatives'])->withCount('items'))
            ->recordUrl(fn ($record): string => GalleryAlbumResource::getUrl('view', ['record' => $record]))
            ->searchPlaceholder('Cari judul album...')
            ->defaultSort('created_at', 'desc')
            ->columns([
                ViewColumn::make('album_mobile')
                    ->label('Album')
                    ->view('filament.tables.columns.gallery-album-mobile')
                    ->hiddenFrom('md'),
                ImageColumn::make('cover_thumbnail')
                    ->label('Sampul')
                    ->state(fn (GalleryAlbum $record): ?string => MediaThumbnail::path($record->coverMedia))
                    ->disk(fn (GalleryAlbum $record): string => MediaThumbnail::disk($record->coverMedia))
                    ->defaultImageUrl(MediaThumbnail::placeholderUrl())
                    ->extraImgAttributes(fn (GalleryAlbum $record): array => [
                        'class' => 'admin-gallery-thumbnail',
                        'alt' => 'Sampul album '.$record->title,
                    ])
                    ->square()
                    ->size(48)
                    ->visibleFrom('md'),
                TextColumn::make('title')
                    ->label('Judul Album')
                    ->wrap()
                    ->searchable()
                    ->sortable()
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
                IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->boolean()
                    ->visibleFrom('lg'),
                TextColumn::make('items_count')
                    ->label('Jumlah Foto')
                    ->suffix(' foto')
                    ->alignCenter()
                    ->visibleFrom('md'),
                TextColumn::make('published_at')
                    ->label('Diterbitkan pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->placeholder('Belum diterbitkan')
                    ->visibleFrom('md')
                    ->sortable(),
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draf',
                        'published' => 'Terbit',
                        'archived' => 'Diarsipkan',
                    ]),
                TernaryFilter::make('is_featured')
                    ->label('Galeri Unggulan')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
                TrashedFilter::make()->label('Status Penghapusan'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Lihat')->icon('heroicon-o-eye'),
                    EditAction::make()->label('Ubah')->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Hapus Album Galeri')
                        ->modalDescription('Album tidak lagi tampil di website. Media di Perpustakaan Media tidak ikut dihapus.')
                        ->modalSubmitActionLabel('Hapus Album'),
                    RestoreAction::make()->label('Pulihkan')->icon('heroicon-o-arrow-path'),
                    ForceDeleteAction::make()
                        ->label('Hapus Permanen')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Hapus Album Secara Permanen')
                        ->modalDescription('Tindakan ini tidak dapat dibatalkan. Media di Perpustakaan Media tetap dipertahankan.')
                        ->modalSubmitActionLabel('Hapus Permanen'),
                ])
                    ->label('Aksi Album')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi Album'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                AdminTable::exportAction(GalleryAlbumExporter::class, GalleryAlbumResource::class),
            ])
            ->emptyStateIcon('heroicon-o-camera')
            ->emptyStateHeading('Belum ada album galeri')
            ->emptyStateDescription('Buat album pertama untuk menampilkan dokumentasi kegiatan dan potensi desa.');
    }
}
