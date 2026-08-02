<?php

namespace App\Support\Exports;

use RuntimeException;

final class PdfExportLimitExceeded extends RuntimeException
{
    public function __construct(public readonly int $limit)
    {
        parent::__construct("Ekspor PDF dibatasi maksimal {$limit} baris.");
    }
}
