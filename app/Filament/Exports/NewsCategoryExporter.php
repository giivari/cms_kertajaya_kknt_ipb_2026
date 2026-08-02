<?php

namespace App\Filament\Exports;

use App\Models\NewsCategory;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class NewsCategoryExporter extends BaseAdminExporter
{
    protected static ?string $model = NewsCategory::class;

    public static function reportTitle(): string { return 'Laporan Kategori Berita'; }

    public static function fileSlug(): string { return 'kategori-berita'; }

    public static function pdfOrientation(): string { return 'P'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama Kategori')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('description')->label('Deskripsi')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('news_count')->label('Jumlah Berita')->counts('news'),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
