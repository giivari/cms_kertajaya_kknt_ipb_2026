<?php

namespace App\Services;

use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use League\Csv\Reader;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

final class ExportArtifactService
{
    public function fail(Export $export, string $reason): void
    {
        $failed = DB::transaction(function () use ($export, $reason): ?Export {
            $locked = Export::query()->whereKey($export->getKey())->lockForUpdate()->first();
            if ($locked && in_array($locked->lifecycle_state, ['pending', 'generating'], true)) {
                $locked->forceFill([
                    'lifecycle_state' => 'failed', 'failure_reason' => $reason,
                ])->save();
                return $locked;
            }

            return null;
        });
        if ($failed) {
            app(ExportAuditService::class)->record($failed, 'export_failed');
        }
    }

    public function path(Export $export, string $format): string
    {
        if (! in_array($format, ['csv', 'xlsx', 'pdf'], true)
            || ! preg_match('/^[A-Za-z0-9_-]+$/D', (string) $export->file_name)) {
            throw new RuntimeException('Identitas artefak ekspor tidak valid.');
        }

        return $export->getFileDirectory().'/'.$export->file_name.'.'.$format;
    }

    /** @param array<string, string> $columnMap */
    public function completeQueued(Export $export, string $format, array $columnMap): bool
    {
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('Format antrean ekspor tidak valid.');
        }

        $current = Export::query()->findOrFail($export->getKey());
        if ($current->lifecycle_state === 'completed') {
            return false;
        }
        if ($current->lifecycle_state !== 'generating' || $current->requested_format !== $format
            || $current->expected_chunks === null || $current->file_disk !== 'admin_exports') {
            throw new RuntimeException('Pekerjaan ekspor belum lengkap atau sudah tidak aktif.');
        }

        $chunks = DB::table('export_chunks')->where('export_id', $current->getKey())->orderBy('page')->get();
        if ($chunks->count() !== (int) $current->expected_chunks
            || $chunks->sum('processed_rows') !== (int) $current->processed_rows
            || $chunks->sum('successful_rows') !== (int) $current->successful_rows) {
            throw new RuntimeException('Manifest chunk ekspor tidak lengkap.');
        }

        $disk = $current->getFileDisk();
        $directory = $current->getFileDirectory();
        $headerPath = $directory.'/headers.csv';
        if (! $disk->exists($headerPath)) {
            throw new RuntimeException('Header ekspor tidak tersedia.');
        }
        $header = $disk->get($headerPath);
        $this->verifyCsv($header, $current->exporter::getCsvDelimiter(), array_values($columnMap), 0);

        $body = $header;
        foreach ($chunks as $index => $chunk) {
            if ((int) $chunk->page !== $index + 1) {
                throw new RuntimeException('Urutan chunk ekspor tidak lengkap.');
            }
            $path = $directory.'/'.str_pad((string) $chunk->page, 16, '0', STR_PAD_LEFT).'.csv';
            if (! $disk->exists($path)) {
                throw new RuntimeException('Chunk ekspor tidak tersedia.');
            }
            $bytes = $disk->get($path);
            if (! hash_equals($chunk->sha256, hash('sha256', $bytes))) {
                throw new RuntimeException('Checksum chunk ekspor tidak cocok.');
            }
            $body .= $bytes;
        }

        if ($format === 'csv') {
            $this->verifyCsv($body, $current->exporter::getCsvDelimiter(), array_values($columnMap), (int) $current->successful_rows);
            $candidate = $this->path($current, 'csv').'.pending';
            if (! $disk->put($candidate, $body)) {
                throw new RuntimeException('Artefak CSV tidak dapat ditulis.');
            }
            $final = $this->path($current, 'csv');
            if (! $disk->move($candidate, $final)) {
                throw new RuntimeException('Artefak CSV tidak dapat difinalisasi.');
            }
        }

        $metadata = $this->verify($current, $format, (int) $current->successful_rows, array_values($columnMap));

        $completed = DB::transaction(function () use ($current, $format, $metadata): bool {
            $locked = Export::query()->whereKey($current->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->lifecycle_state === 'completed') {
                return false;
            }
            if ($locked->lifecycle_state !== 'generating' || $locked->requested_format !== $format
                || (int) $locked->expected_chunks !== (int) $current->expected_chunks
                || (int) $locked->successful_rows !== (int) $current->successful_rows) {
                throw new RuntimeException('Ekspor berubah selama finalisasi.');
            }

            $locked->forceFill([
                'lifecycle_state' => 'completed', 'artifact_path' => $metadata['path'],
                'artifact_size' => $metadata['size'], 'artifact_sha256' => $metadata['sha256'],
                'verified_at' => now(), 'completed_at' => now(), 'failure_reason' => null,
            ])->save();

            return true;
        });

        if ($completed) {
            app(ExportAuditService::class)->record($current->fresh(), 'export_completed');
        }

        return $completed;
    }

    /** @return array{path: string, size: int, sha256: string} */
    public function verify(Export $export, string $format, ?int $expectedRows = null, ?array $expectedHeader = null): array
    {
        if ($export->file_disk !== 'admin_exports') {
            throw new RuntimeException('Disk ekspor tidak valid.');
        }
        $path = $this->path($export, $format);
        $disk = $export->getFileDisk();
        if (! $disk->exists($path)) {
            throw new RuntimeException('Artefak ekspor tidak tersedia.');
        }
        $root = realpath($disk->path('filament_exports'));
        $resolved = realpath($disk->path($path));
        if ($root === false || $resolved === false || ! is_file($resolved)
            || ! str_starts_with($resolved, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            || is_link($disk->path('filament_exports')) || is_link(dirname($disk->path($path)))
            || is_link($disk->path($path))) {
            throw new RuntimeException('Path artefak ekspor tidak aman.');
        }
        $bytes = $disk->get($path);
        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('Artefak ekspor kosong atau tidak terbaca.');
        }

        if ($format === 'csv') {
            $this->verifyCsv($bytes, $export->exporter::getCsvDelimiter(), $expectedHeader, $expectedRows);
        } elseif ($format === 'xlsx') {
            $this->verifyXlsx($disk->path($path), $expectedRows);
        } else {
            $this->verifyPdf($bytes);
        }

        return ['path' => $path, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes)];
    }

    public function assertDownloadable(Export $export, string $format): string
    {
        abort_unless($export->lifecycle_state === 'completed'
            && $export->completed_at !== null
            && \Illuminate\Support\Carbon::createFromTimestamp((int) $export->completed_at)->gt(now()->subDay())
            && $export->requested_format === $format
            && $export->verified_at !== null, 404);

        try {
            $metadata = $this->verify($export, $format, $format === 'pdf' ? null : (int) $export->successful_rows);
        } catch (\Throwable $exception) {
            abort(404);
        }

        abort_unless($export->artifact_path === $metadata['path']
            && (int) $export->artifact_size === $metadata['size']
            && is_string($export->artifact_sha256)
            && hash_equals($export->artifact_sha256, $metadata['sha256']), 404);

        return $metadata['path'];
    }

    public function download(Export $export, string $format, string $fileName, string $mime): StreamedResponse
    {
        // Open the private file while holding the same row lock as cleanup.
        // The opened handle remains readable during streaming even if cleanup
        // starts after this short transaction has committed.
        $stream = DB::transaction(function () use ($export, $format) {
            $locked = Export::query()->whereKey($export->getKey())->lockForUpdate()->firstOrFail();
            $path = $this->assertDownloadable($locked, $format);
            $stream = $locked->getFileDisk()->readStream($path);
            abort_unless(is_resource($stream), 404);

            return $stream;
        });

        $response = response()->streamDownload(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $fileName, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
        $response->setPrivate();

        return $response;
    }

    private function verifyCsv(string $bytes, string $delimiter, ?array $expectedHeader, ?int $expectedRows): void
    {
        if (! str_starts_with($bytes, "\xEF\xBB\xBF")) {
            throw new RuntimeException('CSV tidak memiliki UTF-8 BOM yang diharapkan.');
        }
        $reader = Reader::fromString(substr($bytes, 3));
        $reader->setDelimiter($delimiter);
        $reader->setHeaderOffset(0);
        $header = $reader->getHeader();
        if ($header === [] || ($expectedHeader !== null && $header !== $expectedHeader)) {
            throw new RuntimeException('Header CSV tidak sesuai.');
        }
        $count = 0;
        foreach ($reader->getRecords() as $row) {
            if (count($row) !== count($header)) {
                throw new RuntimeException('Baris CSV tidak sesuai header.');
            }
            $count++;
        }
        if ($expectedRows !== null && $count !== $expectedRows) {
            throw new RuntimeException('Jumlah baris CSV tidak sesuai manifest.');
        }
    }

    private function verifyXlsx(string $absolutePath, ?int $expectedRows): void
    {
        $zip = new ZipArchive();
        if ($zip->open($absolutePath, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('XLSX bukan ZIP yang valid.');
        }
        try {
            foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/worksheets/sheet1.xml'] as $entry) {
                if ($zip->locateName($entry) === false || $zip->getFromName($entry) === false) {
                    throw new RuntimeException('Struktur workbook XLSX tidak lengkap.');
                }
            }
        } finally {
            $zip->close();
        }

        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($absolutePath);
        try {
            $hasSheet = false;
            $rows = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                $hasSheet = true;
                foreach ($sheet->getRowIterator() as $row) {
                    // Force the parser to decode each row, not only the ZIP directory.
                    $row->toArray();
                    $rows++;
                }
            }
            if (! $hasSheet || $rows < 1 || ($expectedRows !== null && $rows !== $expectedRows + 1)) {
                throw new RuntimeException('Jumlah baris atau worksheet XLSX tidak sesuai.');
            }
        } finally {
            $reader->close();
        }
    }

    private function verifyPdf(string $bytes): void
    {
        if (! str_starts_with($bytes, '%PDF-') || ! str_contains(substr($bytes, -2048), '%%EOF')) {
            throw new RuntimeException('PDF tidak lengkap.');
        }
        if ((new Parser())->parseContent($bytes)->getPages() === []) {
            throw new RuntimeException('PDF tidak memiliki halaman yang dapat dibaca.');
        }
    }
}
