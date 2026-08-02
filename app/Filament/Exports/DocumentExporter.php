<?php

namespace App\Filament\Exports;

use App\Models\Document;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class DocumentExporter extends BaseAdminExporter
{
    protected static ?string $model = Document::class;

    public static function reportTitle(): string { return 'Laporan Dokumen'; }

    public static function fileSlug(): string { return 'dokumen'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title')->label('Judul Dokumen')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('category.name')->label('Kategori')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('description')->label('Deskripsi')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('fileMedia.original_filename')->label('Nama Berkas')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('status')->label('Status')->formatStateUsing(ExportValueSanitizer::status(...)),
            ExportColumn::make('download_count')->label('Jumlah Unduhan'),
            ExportColumn::make('published_at')->label('Diterbitkan pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('updated_at')->label('Terakhir Diperbarui')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
