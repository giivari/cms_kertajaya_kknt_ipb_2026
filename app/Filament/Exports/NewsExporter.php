<?php

namespace App\Filament\Exports;

use App\Models\News;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class NewsExporter extends BaseAdminExporter
{
    protected static ?string $model = News::class;

    public static function reportTitle(): string { return 'Laporan Berita'; }

    public static function fileSlug(): string { return 'berita'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title')->label('Judul Berita')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('category.name')->label('Kategori')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('excerpt')->label('Ringkasan')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('status')->label('Status')->formatStateUsing(ExportValueSanitizer::status(...)),
            ExportColumn::make('is_featured')->label('Unggulan')->formatStateUsing(ExportValueSanitizer::boolean(...)),
            ExportColumn::make('published_at')->label('Diterbitkan pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
