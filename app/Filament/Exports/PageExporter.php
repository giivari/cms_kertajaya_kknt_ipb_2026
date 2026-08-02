<?php

namespace App\Filament\Exports;

use App\Models\Page;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class PageExporter extends BaseAdminExporter
{
    protected static ?string $model = Page::class;

    public static function reportTitle(): string { return 'Laporan Halaman'; }

    public static function fileSlug(): string { return 'halaman'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title')->label('Judul Halaman')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('excerpt')->label('Ringkasan')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('status')->label('Status')->formatStateUsing(ExportValueSanitizer::status(...)),
            ExportColumn::make('is_featured')->label('Unggulan')->formatStateUsing(ExportValueSanitizer::boolean(...)),
            ExportColumn::make('seo_title')->label('Judul Mesin Pencari')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('seo_description')->label('Deskripsi Mesin Pencari')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('published_at')->label('Diterbitkan pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
