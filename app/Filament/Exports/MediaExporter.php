<?php

namespace App\Filament\Exports;

use App\Models\Media;
use App\Support\Exports\ExportValueSanitizer;
use BackedEnum;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Support\Number;

final class MediaExporter extends BaseAdminExporter
{
    protected static ?string $model = Media::class;

    public static function reportTitle(): string { return 'Laporan Perpustakaan Media'; }

    public static function fileSlug(): string { return 'perpustakaan-media'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('original_filename')->label('Nama Media')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('mime_type')->label('Jenis Berkas')->formatStateUsing(fn (mixed $state): string => self::mimeLabel($state)),
            ExportColumn::make('size')->label('Ukuran')->formatStateUsing(fn (mixed $state): string => Number::fileSize((int) $state, precision: 1)),
            ExportColumn::make('processing_status')->label('Status Pemrosesan')->formatStateUsing(fn (mixed $state): string => self::processingLabel($state)),
            ExportColumn::make('invisible_watermark_status')->label('Status Tanda Air')->formatStateUsing(fn (mixed $state): string => self::watermarkLabel($state)),
            ExportColumn::make('alt_text')->label('Teks Alternatif')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('caption')->label('Keterangan')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('uploaded_at')->label('Waktu Unggah')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('created_at')->label('Dibuat pada')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }

    private static function mimeLabel(mixed $value): string
    {
        return match ($value) {
            'image/jpeg' => 'Gambar JPEG',
            'image/png' => 'Gambar PNG',
            'image/webp' => 'Gambar WebP',
            'application/pdf' => 'Dokumen PDF',
            default => 'Berkas',
        };
    }

    private static function processingLabel(mixed $value): string
    {
        $value = $value instanceof BackedEnum ? $value->value : $value;

        return match ($value) {
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'processing' => 'Sedang Diproses',
            default => 'Menunggu',
        };
    }

    private static function watermarkLabel(mixed $value): string
    {
        $value = $value instanceof BackedEnum ? $value->value : $value;

        return match ($value) {
            'applied' => 'Diterapkan',
            'verified' => 'Terverifikasi',
            'failed' => 'Gagal',
            'processing' => 'Sedang Diproses',
            'unsupported' => 'Tidak Didukung',
            default => 'Menunggu',
        };
    }
}
