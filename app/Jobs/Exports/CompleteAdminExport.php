<?php

namespace App\Jobs\Exports;

use App\Services\ExportArtifactService;
use Filament\Actions\Exports\Jobs\ExportCompletion;

final class CompleteAdminExport extends ExportCompletion
{
    public function handle(): void
    {
        $service = app(ExportArtifactService::class);
        $format = $this->formats[0]->value ?? null;
        try {
            $created = $service->completeQueued($this->export, (string) $format, $this->columnMap);
        } catch (\Throwable $exception) {
            $service->fail($this->export, 'artifact_verification_failed');
            throw $exception;
        }

        if ($created) {
            // The vendor notification runs only after our verified state commits.
            // A duplicate completion is deliberately silent.
            $this->export->refresh();
            parent::handle();
        }
    }
}
