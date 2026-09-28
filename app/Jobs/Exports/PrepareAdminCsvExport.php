<?php

namespace App\Jobs\Exports;

use App\Services\ExportArtifactService;
use Filament\Actions\Exports\Jobs\PrepareCsvExport;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PrepareAdminCsvExport extends PrepareCsvExport
{
    public function getExportCsvJob(): string
    {
        return ExportAdminCsvChunk::class;
    }

    public function handle(): void
    {
        $format = $this->options['p5_format'] ?? null;
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('Format ekspor tidak valid.');
        }

        DB::transaction(function () use ($format): void {
            $export = Export::query()->whereKey($this->export->getKey())->lockForUpdate()->firstOrFail();
            if ($export->lifecycle_state !== 'pending' || $export->file_disk !== 'admin_exports'
                || $export->requested_format !== $format) {
                throw new RuntimeException('Permintaan ekspor sudah tidak menunggu proses.');
            }
            $export->forceFill(['lifecycle_state' => 'generating'])->save();
        });

        try {
            parent::handle();
            Export::query()->whereKey($this->export->getKey())->where('lifecycle_state', 'generating')
                ->update(['expected_chunks' => max(0, (int) $this->batch()->totalJobs - 1)]);
        } catch (\Throwable $exception) {
            app(ExportArtifactService::class)->fail($this->export, 'prepare_failed');
            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        app(ExportArtifactService::class)->fail($this->export, 'prepare_failed');
    }
}
