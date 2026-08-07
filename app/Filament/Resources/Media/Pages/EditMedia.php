<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Services\MediaUsageService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{

    protected static string $resource = MediaResource::class;

    public function getTitle(): string
    {
        return 'Ubah Informasi Media';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui nama, teks alternatif, atau keterangan tanpa mengganti berkas asli.';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus')
                ->before(function (DeleteAction $action, $record, MediaUsageService $usageService) {
                    if ($usageService->isInUse($record)) {
                        Notification::make()
                            ->danger()
                            ->title('Media tidak dapat dihapus')
                            ->body('Media sedang digunakan dan tidak dapat dihapus.')
                            ->send();
                        $action->cancel();
                    }
                }),
            ForceDeleteAction::make()->label('Hapus Permanen'),
            RestoreAction::make()->label('Pulihkan'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Informasi media berhasil disimpan';
    }
}
