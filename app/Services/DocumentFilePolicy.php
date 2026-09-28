<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/** Validates stored document bytes; it does not execute or convert document content. */
final class DocumentFilePolicy
{
    public const MIMES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function inspect(string $path, string $filename): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (! isset(self::MIMES[$extension]) || ! is_file($path) || ! is_readable($path)
            || ($size = filesize($path)) === false || $size < 1
            || $size > app(MediaInputPolicy::class)->maxKilobytes() * 1024) {
            $this->reject();
        }

        $detected = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $valid = match ($extension) {
            'pdf' => $detected === 'application/pdf' && $this->validPdf($path),
            'docx', 'xlsx' => in_array($detected, [self::MIMES[$extension], 'application/zip', 'application/octet-stream'], true)
                && $this->validOoxml($path, $extension),
            'doc', 'xls' => in_array($detected, [self::MIMES[$extension], 'application/x-ole-storage', 'application/vnd.ms-office', 'application/CDFV2', 'application/octet-stream'], true)
                && $this->validOle($path, $extension),
        };

        if (! $valid) {
            $this->reject('Isi berkas tidak cocok dengan format dokumen atau tidak dapat dibaca.');
        }

        return ['extension' => $extension, 'mime' => self::MIMES[$extension], 'size' => $size, 'checksum' => hash_file('sha256', $path)];
    }

    private function validPdf(string $path): bool
    {
        try {
            return app(MediaInputPolicy::class)->inspect($path, 'document.pdf') === 'application/pdf';
        } catch (ValidationException) {
            return false;
        }
    }

    private function validOoxml(string $path, string $extension): bool
    {
        if (! class_exists(ZipArchive::class)) {
            return false;
        }
        $zip = new ZipArchive;
        $temporary = null;
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            // On Windows, Livewire's long encoded temporary filename can exceed
            // the native path limit used by ZipArchive even when PHP can read it.
            // Recheck the exact bytes under a short, private, disposable name.
            $temporary = 'staging/document-validation/'.Str::uuid().'.zip';
            $bytes = file_get_contents($path);
            if (! is_string($bytes)) {
                return false;
            }
            if (! Storage::disk('local')->put($temporary, $bytes)) {
                Storage::disk('local')->delete($temporary);
                return false;
            }
            if ($zip->open(Storage::disk('local')->path($temporary), ZipArchive::CHECKCONS) !== true) {
                Storage::disk('local')->delete($temporary);
                return false;
            }
        }

        try {
            if ($zip->numFiles < 3 || $zip->numFiles > 2048) {
                return false;
            }
            $total = 0;
            $names = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                if (! $entry) {
                    return false;
                }
                $name = $entry['name'];
                if (str_contains($name, '\\') || str_starts_with($name, '/')
                    || preg_match('~(^|/)\.\.(/|$)~', $name)
                    || preg_match('~(^|/)(vbaProject\.bin|activeX|embeddings|externalLinks)(/|$)~i', $name)) {
                    return false;
                }
                if (in_array($name, $names, true)) {
                    return false;
                }
                $total += (int) $entry['size'];
                if ($total > 50 * 1024 * 1024 || (int) $entry['size'] > 10 * 1024 * 1024) {
                    return false;
                }
                $names[] = $name;
            }
            $main = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
            if (! in_array('[Content_Types].xml', $names, true) || ! in_array('_rels/.rels', $names, true)
                || ! in_array($main, $names, true)
                || ($extension === 'xlsx' && ! collect($names)->contains(fn ($name) => preg_match('~^xl/worksheets/[^/]+\.xml$~', $name)))) {
                return false;
            }
            $types = $this->zipXml($zip, '[Content_Types].xml');
            $relationships = $this->zipXml($zip, '_rels/.rels');
            $body = $this->zipXml($zip, $main);
            if (! $types || ! $relationships || ! $body) {
                return false;
            }
            if ($types->documentElement?->namespaceURI !== 'http://schemas.openxmlformats.org/package/2006/content-types'
                || $relationships->documentElement?->namespaceURI !== 'http://schemas.openxmlformats.org/package/2006/relationships') {
                return false;
            }
            $expectedType = $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml';
            $expectedRoot = $extension === 'docx' ? 'document' : 'workbook';
            $expectedNamespace = $extension === 'docx'
                ? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'
                : 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
            foreach ($names as $name) {
                if (str_ends_with($name, '.rels')) {
                    $relation = $this->zipXml($zip, $name);
                    if (! $relation || preg_match('/TargetMode\s*=\s*["\']External["\']/i', $relation->saveXML())) {
                        return false;
                    }
                }
            }
            $typed = false;
            foreach ($types->getElementsByTagName('Override') as $override) {
                if ($override->getAttribute('PartName') === '/'.$main
                    && $override->getAttribute('ContentType') === $expectedType) {
                    $typed = true;
                }
            }
            $linked = false;
            foreach ($relationships->getElementsByTagName('Relationship') as $relationship) {
                if (ltrim($relationship->getAttribute('Target'), '/') === $main
                    && str_ends_with($relationship->getAttribute('Type'), '/officeDocument')) {
                    $linked = true;
                }
            }
            if ($extension === 'docx' && $body->getElementsByTagNameNS($expectedNamespace, 'body')->length < 1) {
                return false;
            }
            if ($extension === 'xlsx') {
                $sheetName = collect($names)->first(fn ($name) => preg_match('~^xl/worksheets/[^/]+\.xml$~', $name));
                $sheet = $this->zipXml($zip, $sheetName);
                $sheetRelations = $this->zipXml($zip, 'xl/_rels/workbook.xml.rels');
                if (! $sheetRelations || ! $sheet || $sheet->documentElement?->localName !== 'worksheet'
                    || $sheet->documentElement?->namespaceURI !== $expectedNamespace) {
                    return false;
                }
                $sheetId = $body->getElementsByTagNameNS($expectedNamespace, 'sheet')->item(0)?->getAttributeNS(
                    'http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id',
                );
                $sheetLinked = false;
                foreach ($sheetRelations->getElementsByTagName('Relationship') as $relationship) {
                    if ($sheetId && $relationship->getAttribute('Id') === $sheetId
                        && str_ends_with($relationship->getAttribute('Type'), '/worksheet')
                        && $relationship->getAttribute('Target') === 'worksheets/'.basename($sheetName)) {
                        $sheetLinked = true;
                    }
                }
                if (! $sheetLinked) {
                    return false;
                }
            }

            return $typed && $linked
                && $body->documentElement?->localName === $expectedRoot
                && $body->documentElement?->namespaceURI === $expectedNamespace;
        } finally {
            $zip->close();
            if ($temporary !== null) {
                Storage::disk('local')->delete($temporary);
            }
        }
    }

    private function zipXml(ZipArchive $zip, string $name): ?\DOMDocument
    {
        $entry = $zip->statName($name);
        if (! $entry || $entry['size'] > 2 * 1024 * 1024) {
            return null;
        }
        $bytes = $zip->getFromName($name);
        if (! is_string($bytes) || stripos($bytes, '<!DOCTYPE') !== false) {
            return null;
        }
        $document = new \DOMDocument;
        return @$document->loadXML($bytes, LIBXML_NONET) ? $document : null;
    }

    /** The CFB directory and the actual Word/BIFF stream must both agree. */
    private function validOle(string $path, string $extension): bool
    {
        $bytes = file_get_contents($path);
        if (! is_string($bytes) || strlen($bytes) < 1024
            || substr($bytes, 0, 8) !== "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1"
            || substr($bytes, 28, 2) !== "\xfe\xff") {
            return false;
        }
        $shift = unpack('v', substr($bytes, 30, 2))[1];
        if (! in_array($shift, [9, 12], true)) {
            return false;
        }
        $sectorSize = 1 << $shift;
        $sectorCount = intdiv(strlen($bytes) - $sectorSize, $sectorSize);
        $fatCount = $this->u32($bytes, 44);
        $directorySector = $this->u32($bytes, 48);
        if ($fatCount < 1 || $fatCount > $sectorCount || $directorySector >= $sectorCount) {
            return false;
        }
        $difat = [];
        for ($offset = 76; $offset < 512; $offset += 4) {
            $id = $this->u32($bytes, $offset);
            if ($id < $sectorCount) {
                $difat[] = $id;
            }
        }
        $difatSector = $this->u32($bytes, 68);
        $difatCount = $this->u32($bytes, 72);
        if ($difatCount > $sectorCount) {
            return false;
        }
        for ($i = 0; $i < $difatCount; $i++) {
            if ($difatSector >= $sectorCount) {
                return false;
            }
            $sector = substr($bytes, $sectorSize + $difatSector * $sectorSize, $sectorSize);
            for ($offset = 0; $offset < $sectorSize - 4; $offset += 4) {
                $id = $this->u32($sector, $offset);
                if ($id < $sectorCount) {
                    $difat[] = $id;
                }
            }
            $difatSector = $this->u32($sector, $sectorSize - 4);
        }
        if (count($difat) < $fatCount) {
            return false;
        }
        $fat = [];
        foreach (array_slice($difat, 0, $fatCount) as $id) {
            $sector = substr($bytes, $sectorSize + $id * $sectorSize, $sectorSize);
            foreach (unpack('V*', $sector) as $next) {
                $fat[] = $next;
            }
        }
        $directory = $this->chain($bytes, $fat, $directorySector, $sectorSize, $sectorCount);
        if ($directory === null) {
            return false;
        }
        $entries = [];
        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $entry = substr($directory, $offset, 128);
            $length = unpack('v', substr($entry, 64, 2))[1];
            if ($length < 2 || $length > 64 || $length % 2 !== 0) {
                continue;
            }
            $name = iconv('UTF-16LE', 'UTF-8', substr($entry, 0, $length - 2));
            if ($name !== false && in_array(ord($entry[66]), [2, 5], true)) {
                $entries[$name] = ['type' => ord($entry[66]), 'start' => $this->u32($entry, 116), 'size' => $this->u32($entry, 120)];
            }
        }
        if (($entries['Root Entry']['type'] ?? null) !== 5) {
            return false;
        }
        $streamName = $extension === 'doc' ? 'WordDocument' : (isset($entries['Workbook']) ? 'Workbook' : 'Book');
        $stream = $entries[$streamName] ?? null;
        if (($stream['type'] ?? null) !== 2 || $stream['size'] < 4 || $stream['size'] > 10 * 1024 * 1024
            || ($extension === 'doc' && ! isset($entries['0Table']) && ! isset($entries['1Table']))) {
            return false;
        }
        $cutoff = $this->u32($bytes, 56);
        if ($stream['size'] < $cutoff) {
            $root = $entries['Root Entry'];
            $mini = $this->chain($bytes, $fat, $root['start'], $sectorSize, $sectorCount);
            $miniFat = $this->chain($bytes, $fat, $this->u32($bytes, 60), $sectorSize, $sectorCount);
            if ($mini === null || $miniFat === null) {
                return false;
            }
            $miniLinks = array_values(unpack('V*', $miniFat));
            $content = '';
            $cursor = $stream['start'];
            $seen = [];
            while ($cursor < count($miniLinks) && ! isset($seen[$cursor]) && strlen($content) < $stream['size']) {
                $seen[$cursor] = true;
                $content .= substr($mini, $cursor * 64, 64);
                $cursor = $miniLinks[$cursor];
            }
        } else {
            $content = $this->chain($bytes, $fat, $stream['start'], $sectorSize, $sectorCount);
        }
        if (! is_string($content) || strlen($content) < $stream['size']) {
            return false;
        }
        $content = substr($content, 0, $stream['size']);
        return $extension === 'doc'
            ? strlen($content) >= 32 && substr($content, 0, 2) === "\xec\xa5"
            : strlen($content) >= 16 && in_array(substr($content, 0, 2), ["\x09\x08", "\x09\x04", "\x09\x02"], true);
    }

    private function chain(string $bytes, array $fat, int $start, int $sectorSize, int $sectorCount): ?string
    {
        $content = '';
        $seen = [];
        $cursor = $start;
        while ($cursor < $sectorCount && ! isset($seen[$cursor])) {
            $seen[$cursor] = true;
            $content .= substr($bytes, $sectorSize + $cursor * $sectorSize, $sectorSize);
            $cursor = $fat[$cursor] ?? 0xffffffff;
        }
        return $cursor === 0xfffffffe ? $content : null;
    }

    private function u32(string $bytes, int $offset): int
    {
        return unpack('V', substr($bytes, $offset, 4))[1];
    }

    private function reject(string $message = 'Berkas dokumen tidak didukung atau rusak.'): never
    {
        throw ValidationException::withMessages(['document_upload' => $message]);
    }
}
