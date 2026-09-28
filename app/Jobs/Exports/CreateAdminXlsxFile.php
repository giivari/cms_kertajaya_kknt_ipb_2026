<?php

namespace App\Jobs\Exports;

use App\Services\ExportArtifactService;
use Filament\Actions\Exports\Jobs\CreateXlsxFile;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

final class CreateAdminXlsxFile extends CreateXlsxFile
{
    public function handle(): void
    {
        $export = $this->export->fresh();
        if (! $export || $export->lifecycle_state !== 'generating'
            || $export->requested_format !== 'xlsx' || $export->file_disk !== 'admin_exports') {
            throw new RuntimeException('Ekspor XLSX sudah tidak aktif.');
        }

        $disk = $export->getFileDisk();
        $directory = $export->getFileDirectory();
        $candidate = $directory.'/'.$export->file_name.'.xlsx.pending';
        $disk->makeDirectory($directory);
        $writer = app(Writer::class, ['options' => $this->exporter->getXlsxWriterOptions()]);
        $writer->openToFile($disk->path($candidate));

        try {
            $delimiter = $this->exporter::getCsvDelimiter();
            $style = $this->exporter->getXlsxCellStyle();
            $files = [$directory.'/headers.csv', ...collect($disk->files($directory))
                ->filter(fn (string $path): bool => preg_match('/\/[0-9]{16}\.csv$/D', str_replace('\\', '/', $path)) === 1)
                ->sort()->values()->all()];

            foreach ($files as $index => $file) {
                if (! $disk->exists($file)) {
                    throw new RuntimeException('Sumber XLSX tidak lengkap.');
                }
                $stream = $disk->readStream($file);
                if (! is_resource($stream)) {
                    throw new RuntimeException('Sumber XLSX tidak terbaca.');
                }
                try {
                    $reader = Reader::from($stream);
                    $reader->setDelimiter($delimiter);
                    foreach ((new Statement())->process($reader)->getRecords() as $values) {
                        $rowStyle = $index === 0 ? ($this->exporter->getXlsxHeaderCellStyle() ?? $style) : $style;
                        $makeRow = $index === 0 ? $this->exporter->makeXlsxHeaderRow(...) : $this->exporter->makeXlsxRow(...);
                        $writer->addRow($makeRow($values, $rowStyle));
                    }
                } finally {
                    fclose($stream);
                }
            }
            $this->exporter->configureXlsxWriterBeforeClose($writer);
        } finally {
            $writer->close();
        }

        $final = app(ExportArtifactService::class)->path($export, 'xlsx');
        DB::transaction(function () use ($export, $disk, $candidate, $final): void {
            $locked = \Filament\Actions\Exports\Models\Export::query()
                ->whereKey($export->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->lifecycle_state !== 'generating') {
                throw new RuntimeException('Ekspor XLSX berubah selama finalisasi.');
            }
            if (! $disk->move($candidate, $final)) {
                throw new RuntimeException('XLSX gagal difinalisasi.');
            }
        });
    }

    public function failed(\Throwable $exception): void
    {
        app(ExportArtifactService::class)->fail($this->export, 'xlsx_generation_failed');
    }
}
