<?php

namespace App\Filament\Exports;

use App\Models\Location;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class LocationExporter extends BaseAdminExporter
{
    protected static ?string $model = Location::class;

    public static function reportTitle(): string { return 'Laporan Peta dan Lokasi'; }

    public static function fileSlug(): string { return 'peta-lokasi'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama Lokasi')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('category.name')->label('Kategori')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('address')->label('Alamat')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('short_description')->label('Deskripsi Singkat')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('latitude')->label('Garis Lintang'),
            ExportColumn::make('longitude')->label('Garis Bujur'),
            ExportColumn::make('status')->label('Status')->formatStateUsing(ExportValueSanitizer::status(...)),
            ExportColumn::make('sort_order')->label('Urutan'),
            ExportColumn::make('published_at')->label('Diterbitkan pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
