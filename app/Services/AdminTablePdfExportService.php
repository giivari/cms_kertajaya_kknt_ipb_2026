<?php

namespace App\Services;

use App\Filament\Exports\BaseAdminExporter;
use App\Models\Admin;
use App\Support\Exports\AdminTablePdf;
use App\Support\Exports\ExportValueSanitizer;
use App\Support\Exports\PdfExportLimitExceeded;
use Composer\InstalledVersions;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AdminTablePdfExportService
{
    public const MAX_ROWS = 1000;

    /**
     * @param  class-string<BaseAdminExporter>  $exporterClass
     */
    public function download(Builder $query, string $exporterClass): Response
    {
        $rendered = $this->render($query, $exporterClass);
        $fileName = $this->downloadFileName($exporterClass);

        return response($rendered['content'], 200, [
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  class-string<BaseAdminExporter>  $exporterClass
     */
    public function storeForDownload(Builder $query, string $exporterClass, Admin $owner): Export
    {
        $rendered = $this->render($query, $exporterClass);
        $export = Export::create([
            'file_disk' => 'local',
            'file_name' => Str::random(48),
            'exporter' => $exporterClass,
            'processed_rows' => $rendered['totalRows'],
            'total_rows' => $rendered['totalRows'],
            'successful_rows' => $rendered['totalRows'],
            'user_id' => $owner->getKey(),
            'completed_at' => now(),
        ]);
        $path = $this->storedPdfPath($export);

        try {
            if (! $export->getFileDisk()->put($path, $rendered['content'])) {
                throw new RuntimeException('PDF sementara tidak dapat disimpan.');
            }
        } catch (Throwable $exception) {
            $export->deleteFileDirectory();
            $export->delete();

            throw $exception;
        }

        return $export;
    }

    public function temporaryDownloadUrl(Export $export): string
    {
        return URL::temporarySignedRoute(
            'admin.exports.pdf.download',
            now()->addDay(),
            ['export' => $export],
            absolute: false,
        );
    }

    public function storedPdfPath(Export $export): string
    {
        return $export->getFileDirectory().DIRECTORY_SEPARATOR.$export->file_name.'.pdf';
    }

    /**
     * @param  class-string<BaseAdminExporter>  $exporterClass
     * @return array{content: string, totalRows: int}
     */
    private function render(Builder $query, string $exporterClass): array
    {
        $totalRows = (clone $query)->toBase()->getCountForPagination();

        if ($totalRows > self::MAX_ROWS) {
            throw new PdfExportLimitExceeded(self::MAX_ROWS);
        }

        $columns = $exporterClass::getColumns();
        $query = $this->prepareQuery($query, $columns);
        $records = $query->get();
        $columnMap = collect($columns)->mapWithKeys(
            fn (ExportColumn $column): array => [$column->getName() => (string) $column->getLabel()],
        )->all();

        $export = new Export([
            'file_disk' => 'local',
            'exporter' => $exporterClass,
            'total_rows' => $totalRows,
        ]);
        $exporter = $export->getExporter($columnMap, []);
        $rows = $records->map(
            fn ($record): array => array_map(ExportValueSanitizer::pdfText(...), $exporter($record)),
        )->all();

        $generatedAt = now('Asia/Jakarta')->format('d/m/Y H.i').' WIB';
        $this->configureTcpdfFontPath();
        $pdf = new AdminTablePdf($exporterClass::reportTitle(), $exporterClass::pdfOrientation());
        $pdf->SetCreator('CMS Desa Kertajaya');
        $pdf->SetAuthor('CMS Desa Kertajaya');
        $pdf->SetTitle($exporterClass::reportTitle());
        $pdf->SetMargins(10, 24, 10);
        $pdf->SetHeaderMargin(7);
        $pdf->SetFooterMargin(7);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML(view('filament.exports.pdf-table', [
            'columns' => array_values($columnMap),
            'generatedAt' => $generatedAt,
            'recordCount' => $totalRows,
            'reportTitle' => $exporterClass::reportTitle(),
            'rows' => $rows,
        ])->render(), true, false, true, false, '');

        return [
            'content' => $pdf->Output($this->downloadFileName($exporterClass), 'S'),
            'totalRows' => $totalRows,
        ];
    }

    /**
     * @param  class-string<BaseAdminExporter>  $exporterClass
     */
    private function downloadFileName(string $exporterClass): string
    {
        return $exporterClass::fileSlug().'-'.now('Asia/Jakarta')->format('Y-m-d').'.pdf';
    }

    /**
     * @param  array<ExportColumn>  $columns
     */
    private function prepareQuery(Builder $query, array $columns): Builder
    {
        foreach ($columns as $column) {
            $query = $column->applyRelationshipAggregates($query);
            $query = $column->applyEagerLoading($query);
        }

        return $query;
    }

    private function configureTcpdfFontPath(): void
    {
        $fontPaths = [];
        $packagePath = InstalledVersions::getInstallPath('tecnickcom/tc-lib-pdf-font');

        if (is_string($packagePath)) {
            $fontPaths[] = $packagePath.DIRECTORY_SEPARATOR.'target'.DIRECTORY_SEPARATOR.'fonts';
        }

        $fontPaths[] = resource_path('fonts/tcpdf');
        $fontPath = null;

        foreach ($fontPaths as $candidate) {
            if (
                is_readable($candidate.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'helvetica.json')
                && is_readable($candidate.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'helveticab.json')
            ) {
                $fontPath = rtrim($candidate, '/\\').DIRECTORY_SEPARATOR;

                break;
            }
        }

        if ($fontPath === null) {
            throw new RuntimeException('Descriptor font PDF Helvetica tidak tersedia.');
        }

        if (defined('K_PATH_FONTS')) {
            $configuredPath = rtrim((string) constant('K_PATH_FONTS'), '/\\').DIRECTORY_SEPARATOR;

            if ($configuredPath !== $fontPath) {
                throw new RuntimeException('Path font PDF telah dikonfigurasi ke lokasi lain.');
            }

            return;
        }

        define('K_PATH_FONTS', $fontPath);
    }
}
