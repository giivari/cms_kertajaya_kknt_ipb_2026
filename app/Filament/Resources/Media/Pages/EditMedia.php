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
    public ?string $oldFilename = null;


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
            \Filament\Actions\Action::make('restorePristine')
                ->label('Batal Crop (Kembali ke Asli)')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn ($record) => isset($record->metadata['pristine_filename']))
                ->action(function ($record) {
                    $metadata = $record->metadata;
                    $pristineFilename = $metadata['pristine_filename'];
                    
                    $record->filename = $pristineFilename;
                    unset($metadata['pristine_filename']);
                    
                    $record->metadata = $metadata;
                    $record->save();
                    
                    \App\Jobs\ProcessMediaJob::dispatchSync($record);
                    Notification::make()->success()->title('Gambar dikembalikan ke ukuran asli')->send();
                }),
            RestoreAction::make()->label('Pulihkan'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Informasi media berhasil disimpan';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldFilename = $this->record->filename;
        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->oldFilename && $this->oldFilename !== $this->record->filename) {
            $metadata = $this->record->metadata ?? [];
            if (!isset($metadata['pristine_filename'])) {
                $metadata['pristine_filename'] = $this->oldFilename;
                $this->record->metadata = $metadata;
                $this->record->saveQuietly();
            }

            \App\Jobs\ProcessMediaJob::dispatchSync($this->record);
        }
    }
}
