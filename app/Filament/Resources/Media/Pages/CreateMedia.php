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
        $filePath = is_array($data['filename']) ? array_values($data['filename'])[0] : $data['filename'];

        $fullPath = Storage::disk('local')->path($filePath);
        $fileSize = filesize($fullPath);
        $mimeType = mime_content_type($fullPath);

        // $data['file'] was handled by Filament's FileUpload, which saved it to local disk
        // Create the media record
        $record = static::getModel()::create([
            'original_filename' => $data['original_filename'] ?? basename($filePath),
            'filename' => basename($filePath),
            'directory' => dirname($filePath),
            'mime_type' => $mimeType,
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
            'size' => $fileSize,
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
}
