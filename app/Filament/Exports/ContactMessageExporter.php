<?php

namespace App\Filament\Exports;

use App\Enums\ContactStatus;
use App\Models\ContactMessage;
use App\Support\Exports\ExportValueSanitizer;
use BackedEnum;
use Filament\Actions\Exports\ExportColumn;

final class ContactMessageExporter extends BaseAdminExporter
{
    protected static ?string $model = ContactMessage::class;

    public static function reportTitle(): string { return 'Laporan Pesan Masuk (Data Internal)'; }

    public static function fileSlug(): string { return 'pesan-masuk-internal'; }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nama Pengirim')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('contact_type')->label('Jenis Kontak')->formatStateUsing(fn (mixed $state): string => match ($state) {
                'email' => 'Email', 'phone' => 'Telepon/WhatsApp', default => ExportValueSanitizer::text($state),
            }),
            ExportColumn::make('contact_value')->label('Kontak')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('subject')->label('Subjek')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('message')->label('Pesan')->formatStateUsing(ExportValueSanitizer::text(...)),
            ExportColumn::make('status')->label('Status')->formatStateUsing(fn (mixed $state): string => self::statusLabel($state)),
            ExportColumn::make('read_at')->label('Dibaca pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('archived_at')->label('Diarsipkan pada')->formatStateUsing(ExportValueSanitizer::date(...)),
            ExportColumn::make('created_at')->label('Diterima pada')->formatStateUsing(ExportValueSanitizer::date(...)),
        ];
    }

    private static function statusLabel(mixed $state): string
    {
        $value = $state instanceof BackedEnum ? $state->value : $state;

        return ContactStatus::tryFrom((string) $value)?->getLabel() ?? ExportValueSanitizer::text($value);
    }
}
