<?php

namespace App\Filament\Resources\Media\Pages;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Filament\Resources\Media\MediaResource;
use App\Jobs\ProcessMediaJob;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateMedia extends CreateRecord
{
    protected static bool $canCreateAnother = false;


    protected static string $resource = MediaResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            \App\Filament\Support\PreviewAction::make('media'),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterCreate(): void
    {
        \App\Services\AuditLogService::log('media_uploaded', $this->record, null, [
            'processing_status' => $this->record->refresh()->processing_status?->value,
        ]);
        app(\App\Services\Preview\PreviewDraftStore::class)->forget(static::class, null);
    }

    public function getTitle(): string
    {
        return 'Unggah Media';
    }

    public function getSubheading(): ?string
    {
        return 'Berkas disimpan secara privat, lalu diproses dan diverifikasi sebelum dapat digunakan.';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $filePath = is_array($data['file']) ? array_values($data['file'])[0] : $data['file'];

        $policy = app(\App\Services\MediaInputPolicy::class);
        $fullPath = $policy->originalPath($filePath);
        $fileSize = filesize($fullPath);
        $mimeType = $policy->inspect($fullPath, basename($filePath));
        $dimensions = @getimagesize($fullPath);

        // $data['file'] was handled by Filament's FileUpload, which saved it to local disk
        // Create the media record
        $record = static::getModel()::create([
            'original_filename' => $data['original_filename'] ?? basename($filePath),
            'filename' => basename($filePath),
            'directory' => dirname($filePath),
            'mime_type' => $mimeType,
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
            'size' => $fileSize,
            'width' => $dimensions ? $dimensions[0] : null,
            'height' => $dimensions ? $dimensions[1] : null,
            'checksum' => hash_file('sha256', $fullPath),
            'disk' => 'local',
            'alt_text' => $data['alt_text'] ?? null,
            'caption' => $data['caption'] ?? null,
            'processing_status' => MediaProcessingStatus::PENDING,
            'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
        ]);

        ProcessMediaJob::dispatchSync($record);

        return $record;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Media berhasil diunggah dan diproses';
    }

    protected function getCreatedNotification(): ?\Filament\Notifications\Notification
    {
        if ($this->record->refresh()->processing_status !== MediaProcessingStatus::COMPLETED) {
            return \Filament\Notifications\Notification::make()->warning()
                ->title('Berkas tersimpan privat, tetapi belum lolos pemrosesan publik.');
        }

        return parent::getCreatedNotification();
    }
}
