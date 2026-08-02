<?php

namespace App\Filament\Exports;

use App\Models\Menu;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class MenuExporter extends BaseAdminExporter
{
    protected static ?string $model = Menu::class;

    public static function reportTitle(): string { return 'Laporan Navigasi'; }

    public static function fileSlug(): string { return 'navigasi'; }

    public static function pdfOrientation(): string { return 'P'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama Menu')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('location')->label('Posisi Menu')->formatStateUsing(fn (mixed $state): string => ExportValueSanitizer::text(Menu::supportedLocations()[$state] ?? $state)),
            ExportColumn::make('description')->label('Keterangan')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('all_items_count')->label('Jumlah Tautan')->counts('allItems'),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
