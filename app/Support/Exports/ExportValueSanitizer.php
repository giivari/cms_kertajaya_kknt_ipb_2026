<?php

namespace App\Support\Exports;

use BackedEnum;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

final class ExportValueSanitizer
{
    public static function text(mixed $state): string
    {
        if ($state instanceof BackedEnum) {
            $state = $state->value;
        }

        if ($state instanceof DateTimeInterface) {
            $state = $state->format(DateTimeInterface::ATOM);
        }

        if (is_bool($state)) {
            $state = $state ? 'Ya' : 'Tidak';
        }

        if (! is_scalar($state) && $state !== null) {
            $state = '';
        }

        $text = html_entity_decode(strip_tags((string) $state), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if (preg_match('/^[=+\-@]/u', $text) === 1) {
            return "'{$text}";
        }

        return $text;
    }

    public static function pdfText(mixed $state): string
    {
        $text = self::text($state);

        return preg_replace("/^'(?=[=+\-@])/u", '', $text) ?? $text;
    }

    public static function date(mixed $state): string
    {
        if (blank($state)) {
            return 'Belum tersedia';
        }

        $date = $state instanceof CarbonInterface
            ? $state
            : Carbon::parse($state);

        return $date->copy()->timezone('Asia/Jakarta')->format('d/m/Y H.i');
    }

    public static function boolean(mixed $state, string $trueLabel = 'Ya', string $falseLabel = 'Tidak'): string
    {
        return (bool) $state ? $trueLabel : $falseLabel;
    }

    public static function status(mixed $state): string
    {
        $state = $state instanceof BackedEnum ? $state->value : $state;

        return match ($state) {
            'published' => 'Terbit',
            'archived' => 'Diarsipkan',
            'draft' => 'Draf',
            default => self::text($state),
        };
    }
}
