<?php

namespace App\Jobs\Exports;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Services\ExportArtifactService;
use Filament\Actions\Exports\Jobs\ExportCsv;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;
use RuntimeException;
use SplTempFileObject;

final class ExportAdminCsvChunk extends ExportCsv
{
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        auth()->setUser($this->export->user);
        try {
            $csv = Writer::from(new SplTempFileObject());
            $csv->setDelimiter($this->exporter::getCsvDelimiter());
            $query = EloquentSerializeFacade::unserialize($this->query);
            foreach ($this->exporter->getCachedColumns() as $column) {
                $column->applyRelationshipAggregates($query);
                $column->applyEagerLoading($query);
            }

            $processed = 0;
            $successful = 0;
            foreach ($query->find($this->records) as $record) {
                try {
                    $csv->insertOne(($this->exporter)($record));
                    $successful++;
                } catch (\Throwable $exception) {
                    report(new RuntimeException('Satu baris ekspor gagal diformat: '.get_class($exception)));
                }
                $processed++;
            }

            $bytes = $csv->toString();
            $checksum = hash('sha256', $bytes);
            $path = $this->export->getFileDirectory().'/'.str_pad((string) $this->page, 16, '0', STR_PAD_LEFT).'.csv';
            DB::transaction(function () use ($path, $bytes, $checksum, $processed, $successful): void {
                $export = Export::query()->whereKey($this->export->getKey())->lockForUpdate()->firstOrFail();
                if ($export->lifecycle_state !== 'generating' || $export->file_disk !== 'admin_exports') {
                    throw new RuntimeException('Ekspor sudah tidak menerima chunk.');
                }
                $existing = DB::table('export_chunks')->where('export_id', $export->getKey())
                    ->where('page', $this->page)->first();
                if ($existing) {
                    if (! $export->getFileDisk()->exists($path)
                        || ! hash_equals($existing->sha256, hash('sha256', $export->getFileDisk()->get($path)))) {
                        throw new RuntimeException('Chunk lama tidak cocok dengan manifest.');
                    }
                    return;
                }

                if (! $export->getFileDisk()->put($path, $bytes)
                    || ! hash_equals($checksum, hash('sha256', $export->getFileDisk()->get($path)))) {
                    throw new RuntimeException('Penulisan chunk ekspor tidak terverifikasi.');
                }
                DB::table('export_chunks')->insert([
                    'export_id' => $export->getKey(), 'page' => $this->page,
                    'processed_rows' => $processed, 'successful_rows' => $successful,
                    'sha256' => $checksum,
                ]);
                $export->increment('processed_rows', $processed);
                $export->increment('successful_rows', $successful);
            });
        } finally {
            auth()->forgetGuards();
        }
    }

    public function failed(\Throwable $exception): void
    {
        app(ExportArtifactService::class)->fail($this->export, 'chunk_failed');
    }
}
