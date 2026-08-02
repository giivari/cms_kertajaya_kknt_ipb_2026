<?php

namespace App\Support\Exports;

final class AdminTablePdf extends \TCPDF
{
    public function __construct(
        private readonly string $reportTitle,
        string $orientation,
    ) {
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8', false);
    }

    public function Header(): void
    {
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 5, 'CMS Desa Kertajaya', 0, 1, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(0, 5, $this->reportTitle, 0, 1, 'L');
        $this->SetDrawColor(203, 213, 225);
        $this->Line($this->GetX(), $this->GetY() + 1, $this->getPageWidth() - $this->getMargins()['right'], $this->GetY() + 1);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 5, 'Halaman '.$this->getAliasNumPage().' dari '.$this->getAliasNbPages(), 0, 0, 'C');
    }
}
