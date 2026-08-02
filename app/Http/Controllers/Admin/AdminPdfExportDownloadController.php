<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Exports\BaseAdminExporter;
use App\Models\Admin;
use App\Services\AdminTablePdfExportService;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminPdfExportDownloadController
{
    public function __invoke(
        Request $request,
        Export $export,
        AdminTablePdfExportService $service,
    ): StreamedResponse {
        abort_unless($request->hasValidSignature(absolute: false), 403);

        $admin = $request->user();

        abort_unless($admin instanceof Admin, 403);
        abort_unless($export->user()->is($admin), 403);
        abort_unless($export->file_disk === 'local', 404);
        abort_unless(is_a($export->exporter, BaseAdminExporter::class, true), 404);

        $path = $service->storedPdfPath($export);
        $disk = $export->getFileDisk();

        abort_unless($disk->exists($path), 404);

        $date = $export->created_at?->timezone('Asia/Jakarta')->format('Y-m-d')
            ?? now('Asia/Jakarta')->format('Y-m-d');
        $fileName = $export->exporter::fileSlug().'-'.$date.'.pdf';

        return $disk->download($path, $fileName, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
