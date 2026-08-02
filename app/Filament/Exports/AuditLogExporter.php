<?php

namespace App\Filament\Exports;

use App\Models\AuditLog;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\Exports\ExportColumn;

final class AuditLogExporter extends BaseAdminExporter
{
    protected static ?string $model = AuditLog::class;

    public static function reportTitle(): string { return 'Laporan Log Aktivitas (Data Internal)'; }

    public static function fileSlug(): string { return 'log-aktivitas-internal'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('admin.name')->label('Admin')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('event_type')->label('Jenis Kejadian')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('subject_type')->label('Jenis Data')->formatStateUsing(fn (mixed $state): string => ExportValueSanitizer::text(class_basename((string) $state))),
            ExportColumn::make('subject_id')->label('Referensi Data')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('ip_address')->label('Alamat IP')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('created_at')->label('Waktu Kejadian')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }
}
