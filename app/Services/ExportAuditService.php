<?php

namespace App\Services;

use App\Models\AuditLog;
use Filament\Actions\Exports\Models\Export;

final class ExportAuditService
{
    public function record(Export $export, string $event): void
    {
        try {
            AuditLog::create([
                'admin_id' => $export->user_id,
                'event_type' => $event,
                'subject_type' => Export::class,
                'subject_id' => (string) $export->getKey(),
                'new_values' => [
                    'format' => $export->requested_format,
                    'state' => $export->lifecycle_state,
                ],
            ]);
        } catch (\Throwable $exception) {
            // Observability must never turn a verified file into a failed export.
            report(new \RuntimeException('Audit ekspor tidak tersimpan: '.get_class($exception)));
        }
    }
}
