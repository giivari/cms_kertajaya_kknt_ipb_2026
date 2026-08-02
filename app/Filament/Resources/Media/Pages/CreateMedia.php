<?php

namespace App\Filament\Resources\Media\Pages;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Support\Concerns\HasCreatePreview;
use App\Jobs\ProcessMediaJob;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateMedia extends CreateRecord
{
    use HasCreatePreview;

    protected static string $resource = MediaResource::class;

    public function getTitle(): string
    {
        return 'Unggah Media';
    }

    public function getSubheading(): ?string
    {
        return 'Berkas disimpan secara privat, lalu diproses dan diverifikasi sebelum dapat digunakan.';
    }

    protected function previewType(): string
    {
        return 'media';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $filePath = is_array($data['file']) ? array_values($data['file'])[0] : $data['file'];

        $fullPath = Storage::disk('private')->path($filePath);
        $fileSize = filesize($fullPath);
        $mimeType = mime_content_type($fullPath);

        // $data['file'] was handled by Filament's FileUpload, which saved it to private disk
        // Create the media record
        $record = static::getModel()::create([
            'original_filename' => $data['original_filename'] ?? basename($filePath),
            'filename' => basename($filePath),
            'directory' => dirname($filePath),
            'mime_type' => $mimeType,
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
            'size' => $fileSize,
            'disk' => 'private',
            'alt_text' => $data['alt_text'] ?? null,
            'caption' => $data['caption'] ?? null,
            'processing_status' => MediaProcessingStatus::PENDING,
            'invisible_watermark_status' => InvisibleWatermarkStatus::PENDING,
        ]);

        ProcessMediaJob::dispatch($record);

        return $record;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Media berhasil diunggah dan masuk antrean pemrosesan';
    }
}
