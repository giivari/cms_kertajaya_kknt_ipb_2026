<?php

namespace App\Filament\Exports;

use App\Models\GalleryAlbum;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class GalleryAlbumExporter extends BaseAdminExporter
{
    protected static ?string $model = GalleryAlbum::class;

    public static function reportTitle(): string { return 'Laporan Album Galeri'; }

    public static function fileSlug(): string { return 'album-galeri'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title')->label('Judul Album')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('description')->label('Deskripsi')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('status')->label('Status')->formatStateUsing(ExportValueSanitizer::status(...)),
            ExportColumn::make('is_featured')->label('Unggulan')->formatStateUsing(ExportValueSanitizer::boolean(...)),
            ExportColumn::make('items_count')->label('Jumlah Foto')->counts('items'),
            ExportColumn::make('published_at')->label('Diterbitkan pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
