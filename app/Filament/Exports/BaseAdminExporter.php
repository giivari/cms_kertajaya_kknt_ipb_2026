<?php

namespace App\Filament\Exports;

use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

abstract class BaseAdminExporter extends Exporter
{
    abstract public static function reportTitle(): string;

    abstract public static function fileSlug(): string;

    public static function pdfOrientation(): string
    {
        return 'L';
    }

    public function getFileDisk(): string
    {
        return 'local';
    }

    public function getFileName(Export $export): string
    {
        return static::fileSlug().'-'.now('Asia/Jakarta')->format('Y-m-d').'-'.$export->getKey();
    }

    public function getXlsxHeaderCellStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor('0F766E')
            ->setShouldWrapText();
    }

    public function getXlsxCellStyle(): Style
    {
        return (new Style)->setShouldWrapText();
    }

    public function configureXlsxWriterBeforeClose(Writer $writer): Writer
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Data');
        $sheet->setSheetView(
            (new SheetView)->setFreezeRow(2),
        );

        if ($columnCount = count(static::getColumns())) {
            $sheet->setColumnWidth(22, ...range(1, $columnCount));
        }

        return $writer;
    }

    public static function getCompletedNotificationTitle(Export $export): string
    {
        return 'Ekspor siap diunduh';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $successful = number_format($export->successful_rows, 0, ',', '.');
        $failed = $export->getFailedRowsCount();

        $body = "{$successful} baris berhasil diekspor.";

        if ($failed > 0) {
            $body .= ' '.number_format($failed, 0, ',', '.').' baris gagal diproses.';
        }

        return $body;
    }
}
