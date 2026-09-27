<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use App\Services\MediaUsageService;
use App\Services\MediaDeletionService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    public ?string $oldFilename = null;


    protected static string $resource = MediaResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            \App\Filament\Support\PreviewAction::make('media', editing: true),
            $this->getCancelFormAction(),
        ];
    }

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
                ->using(fn ($record, MediaDeletionService $service): bool => $service->archive($record))
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
            ForceDeleteAction::make()
                ->label('Hapus Permanen')
                ->using(fn ($record, MediaDeletionService $service): bool => $service->permanentlyDelete($record)),
            \Filament\Actions\Action::make('restorePristine')
                ->label('Batal Crop (Kembali ke Asli)')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn ($record) => isset($record->metadata['pristine_filename']))
                ->action(function ($record) {
                    $metadata = $record->metadata;
                    $pristineFilename = $metadata['pristine_filename'];
                    $policy = app(\App\Services\MediaInputPolicy::class);
                    $path = $policy->originalPath('originals/'.$pristineFilename);
                    $mime = $policy->inspect($path, $pristineFilename);
                    $dimensions = @getimagesize($path);
                    $record->fill([
                        'mime_type' => $mime, 'extension' => pathinfo($pristineFilename, PATHINFO_EXTENSION),
                        'size' => filesize($path), 'width' => $dimensions ? $dimensions[0] : null,
                        'height' => $dimensions ? $dimensions[1] : null, 'checksum' => hash_file('sha256', $path),
                    ]);
                    
                    $record->filename = $pristineFilename;
                    unset($metadata['pristine_filename']);
                    
                    $record->metadata = $metadata;
                    $record->save();
                    
                    \App\Jobs\ProcessMediaJob::dispatchSync($record);
                    \App\Services\AuditLogService::log('media_original_reselected', $record);
                    if ($record->refresh()->last_processing_status === 'completed') {
                        Notification::make()->success()->title('Gambar dikembalikan ke ukuran asli')->send();
                    } else {
                        Notification::make()->warning()->title('Berkas privat belum lolos pemrosesan publik.')->send();
                    }
                }),
            RestoreAction::make()
                ->label('Pulihkan')
                ->using(fn ($record, MediaDeletionService $service): bool => $service->restore($record)),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Informasi media berhasil disimpan';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Inject the full path so Filament's FileUpload can preview it
        $data['file'] = 'originals/' . $this->record->filename;
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldFilename = $this->record->filename;
        
        if (isset($data['file'])) {
            $newPath = is_array($data['file']) ? array_values($data['file'])[0] : $data['file'];
            if (basename($newPath) !== $this->record->filename) {
                $policy = app(\App\Services\MediaInputPolicy::class);
                $path = $policy->originalPath($newPath);
                $data['mime_type'] = $policy->inspect($path, basename($newPath));
                $dimensions = @getimagesize($path);
                $data['disk'] = 'local';
                $data['directory'] = 'originals';
                $data['extension'] = pathinfo($newPath, PATHINFO_EXTENSION);
                $data['size'] = filesize($path);
                $data['width'] = $dimensions ? $dimensions[0] : null;
                $data['height'] = $dimensions ? $dimensions[1] : null;
                $data['checksum'] = hash_file('sha256', $path);
            }
            $data['filename'] = basename($newPath);
            unset($data['file']);
        }
        
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
        \App\Services\AuditLogService::log('media_details_updated', $this->record, null, [
            'processing_status' => $this->record->refresh()->processing_status?->value,
        ]);
        app(\App\Services\Preview\PreviewDraftStore::class)->forget(static::class, $this->record->id);
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->record->refresh()->last_processing_status === 'failed') {
            return Notification::make()->warning()->title('Informasi tersimpan; pemrosesan berkas belum berhasil.')
                ->body('Derivative aktif sebelumnya dipertahankan bila tersedia.');
        }

        return parent::getSavedNotification();
    }
}
