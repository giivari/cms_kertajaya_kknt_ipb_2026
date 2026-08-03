<?php

namespace App\Filament\Resources\News\Tables;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Filament\Exports\NewsExporter;
use App\Filament\Resources\News\NewsResource;
use App\Filament\Support\AdminTable;
use App\Models\News;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['category', 'featuredMedia']))
            ->recordUrl(fn ($record): string => NewsResource::getUrl('view', ['record' => $record]))
            ->searchPlaceholder('Cari judul berita...')
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('featured_media_thumbnail')
                    ->label('Gambar')
                    ->state(fn (News $record): ?string => self::getFeaturedMediaPath($record))
                    ->disk(fn (News $record): string => $record->featuredMedia?->disk ?? (string) config('filament.default_filesystem_disk', 'public'))
                    ->defaultImageUrl(self::getThumbnailPlaceholderUrl())
                    ->extraImgAttributes(['class' => 'admin-news-thumbnail'])
                    ->square()
                    ->size(44)
                    ->visibleFrom('md'),
                TextColumn::make('title')
                    ->label('Judul Berita')
                    ->description(fn (News $record): ?string => filled($record->excerpt) ? Str::limit($record->excerpt, 72) : null)
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
                SelectFilter::make('news_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_featured')
                    ->label('Berita Unggulan')
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
                        ->modalHeading('Hapus Berita')
                        ->modalDescription('Berita akan dihapus dari pengelolaan dan tidak lagi tampil di website.')
                        ->modalSubmitActionLabel('Hapus'),
                    RestoreAction::make()->label('Pulihkan')->icon('heroicon-o-arrow-path'),
                    ForceDeleteAction::make()
                        ->label('Hapus Permanen')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Hapus Berita Secara Permanen')
                        ->modalDescription('Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Hapus Permanen'),
                ])
                    ->label('Aksi Berita')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi Berita'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                AdminTable::exportAction(NewsExporter::class, NewsResource::class),
            ])
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateHeading('Belum ada berita')
            ->emptyStateDescription('Buat berita pertama untuk membagikan informasi kepada warga.');
    }

    private static function getFeaturedMediaPath(News $record): ?string
    {
        $media = $record->featuredMedia;

        if (
            (! $media)
            || ($media->processing_status !== MediaProcessingStatus::COMPLETED)
            || ($media->invisible_watermark_status !== InvisibleWatermarkStatus::VERIFIED)
            || (! str_starts_with($media->mime_type, 'image/'))
        ) {
            return null;
        }

        $path = trim($media->directory, '/').'/'.$media->filename;

        try {
            return Storage::disk($media->disk)->exists($path) ? $path : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function getThumbnailPlaceholderUrl(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" role="img" aria-label="Gambar tidak tersedia">
  <rect width="44" height="44" rx="8" fill="#E4ECE9"/>
  <path d="M13 14.5h18a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H13a1.5 1.5 0 0 1-1.5-1.5V16a1.5 1.5 0 0 1 1.5-1.5Zm0 13h18l-5.2-5.2-3.8 3.8-2.6-2.6-6.4 4Zm13-7.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" fill="#60766F"/>
</svg>
SVG;

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
}
