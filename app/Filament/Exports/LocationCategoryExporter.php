<?php

namespace App\Filament\Exports;

use App\Models\LocationCategory;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class LocationCategoryExporter extends BaseAdminExporter
{
    protected static ?string $model = LocationCategory::class;

    public static function reportTitle(): string { return 'Laporan Kategori Lokasi'; }

    public static function fileSlug(): string { return 'kategori-lokasi'; }

    public static function pdfOrientation(): string { return 'P'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama Kategori')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('description')->label('Deskripsi')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('locations_count')->label('Jumlah Lokasi')->counts('locations'),
            ExportColumn::make('sort_order')->label('Urutan'),
            ExportColumn::make('is_active')->label('Aktif')->formatStateUsing(ExportValueSanitizer::boolean(...)),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
