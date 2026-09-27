<?php

namespace App\Support\Exports;

use App\Filament\Exports\BaseAdminExporter;
use App\Models\Admin;
use App\Services\ExportArtifactService;
use Filament\Actions\Exports\Downloaders\CsvDownloader;
use Filament\Actions\Exports\Models\Export;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ControlledCsvDownloader extends CsvDownloader
{
    public function __invoke(Export $export): StreamedResponse
    {
        $admin = auth()->user();
        abort_unless(request()->hasValidSignature(absolute: false), 403);
        abort_unless($admin instanceof Admin && $export->user()->is($admin), 403);
        abort_unless(is_a($export->exporter, BaseAdminExporter::class, true), 404);

        return app(ExportArtifactService::class)->download(
            $export, 'csv', $export->file_name.'.csv', 'text/csv; charset=UTF-8',
        );
    }
}
