<?php

namespace App\Filament\Exports;

use App\Models\DocumentCategory;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class DocumentCategoryExporter extends BaseAdminExporter
{
    protected static ?string $model = DocumentCategory::class;

    public static function reportTitle(): string { return 'Laporan Kategori Dokumen'; }

    public static function fileSlug(): string { return 'kategori-dokumen'; }

    public static function pdfOrientation(): string { return 'P'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama Kategori')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('description')->label('Deskripsi')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('documents_count')->label('Jumlah Dokumen')->counts('documents'),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
