<?php

namespace App\Filament\Support;

use App\Filament\Exports\BaseAdminExporter;
use App\Models\Admin;
use App\Services\AdminTablePdfExportService;
use App\Support\Exports\PdfExportLimitExceeded;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

final class AdminTable
{
    public static function configure(Table $table, string $classes): Table
    {
        return $table
            ->extraAttributes(['class' => trim("admin-table-shell {$classes}")])
            ->columnManager(false)
            ->filtersTriggerAction(fn (Action $action): Action => $action
                ->view('filament.tables.actions.filter-trigger')
                ->extraAttributes(['class' => 'admin-table-filter-trigger'], merge: true));
    }

    public static function filterBadge(?string $badge): ?string
    {
        return ((int) $badge > 0) ? $badge : null;
    }

    /**
     * @param  class-string<BaseAdminExporter>  $exporter
     * @param  class-string<Resource>  $resource
     */
    public static function exportAction(string $exporter, string $resource): ActionGroup
    {
        $canExport = fn (): bool => $resource::canViewAny();

        return ActionGroup::make([
            ExportAction::make('exportCsv')
                ->label('CSV')
                ->icon('heroicon-o-document-text')
                ->exporter($exporter)
                ->formats([ExportFormat::Csv])
                ->columnMapping(false)
                ->chunkSize(100)
                ->maxRows(10_000)
                ->fileDisk('local')
                ->authorize($canExport)
                ->modalHeading('Ekspor CSV')
                ->modalDescription('Data akan mengikuti pencarian, filter, dan urutan tabel saat ini. Maksimal 10.000 baris.')
                ->modalSubmitActionLabel('Mulai Ekspor'),
            ExportAction::make('exportXlsx')
                ->label('Excel (.xlsx)')
                ->icon('heroicon-o-table-cells')
                ->exporter($exporter)
                ->formats([ExportFormat::Xlsx])
                ->columnMapping(false)
                ->chunkSize(100)
                ->maxRows(10_000)
                ->fileDisk('local')
                ->authorize($canExport)
                ->modalHeading('Ekspor Excel')
                ->modalDescription('Data akan mengikuti pencarian, filter, dan urutan tabel saat ini. Maksimal 10.000 baris.')
                ->modalSubmitActionLabel('Mulai Ekspor'),
            Action::make('exportPdf')
                ->label('PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->authorize($canExport)
                ->action(function (Action $action, $livewire) use ($exporter) {
                    if (! $livewire instanceof HasTable) {
                        $action->failure();

                        return null;
                    }

                    $admin = auth()->user();

                    if (! $admin instanceof Admin) {
                        $action->failure();

                        return null;
                    }

                    try {
                        $service = app(AdminTablePdfExportService::class);
                        $export = $service->storeForDownload(
                            $livewire->getTableQueryForExport(),
                            $exporter,
                            $admin,
                        );

                        return redirect()->to($service->temporaryDownloadUrl($export));
                    } catch (PdfExportLimitExceeded $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Data terlalu banyak untuk PDF')
                            ->body("PDF dibatasi maksimal {$exception->limit} baris. Gunakan CSV atau Excel untuk data yang lebih besar.")
                            ->send();

                        $action->failure();

                        return null;
                    }
                }),
        ])
            ->label('Ekspor')
            ->icon('heroicon-o-arrow-down-tray')
            ->button()
            ->color('gray')
            ->extraAttributes(['class' => 'admin-table-export-trigger'])
            ->extraDropdownAttributes(['class' => 'admin-table-export-control'], merge: true);
    }
}
