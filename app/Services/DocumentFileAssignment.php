<?php

namespace App\Services;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Runs inside the Document create/edit transaction. Never changes a prior source file. */
final class DocumentFileAssignment
{
    public function assign(array $data, ?int $currentId = null): int
    {
        $uploadedPath = $data['document_upload'] ?? null;
        if (is_array($uploadedPath)) {
            $uploadedPath = collect($uploadedPath)->first();
        }

        if (is_string($uploadedPath) && $uploadedPath !== '') {
            if (! preg_match('~^originals/[a-f0-9-]{36}\.(pdf|doc|docx|xls|xlsx)$~D', $uploadedPath)) {
                $this->reject();
            }
            $disk = Storage::disk('local');
            $root = realpath($disk->path('originals'));
            $candidate = $disk->path($uploadedPath);
            $path = realpath($candidate);
            if ($root === false || $path === false || is_link($candidate) || dirname($path) !== $root) {
                $this->reject();
            }
            $filename = $data['document_upload_name'] ?? basename($uploadedPath);
            if (! is_string($filename) || strlen($filename) > 255) {
                $this->reject();
            }
            $file = app(DocumentFilePolicy::class)->inspect($path, $filename);
            if ($file['extension'] !== strtolower(pathinfo($uploadedPath, PATHINFO_EXTENSION))) {
                $this->reject();
            }

            $media = Media::create([
                'disk' => 'local',
                'directory' => 'originals',
                'filename' => basename($uploadedPath),
                'original_filename' => $filename,
                'extension' => $file['extension'],
                'mime_type' => $file['mime'],
                'size' => $file['size'],
                'checksum' => $file['checksum'],
                'metadata' => ['document_validation' => ['version' => 1, 'format' => $file['extension']]],
                'processing_status' => MediaProcessingStatus::COMPLETED,
                'invisible_watermark_status' => InvisibleWatermarkStatus::UNSUPPORTED,
            ]);

            return $media->getKey();
        }

        $selectedId = $data['file_media_id'] ?? $currentId;
        if (filter_var($selectedId, FILTER_VALIDATE_INT) === false || (int) $selectedId < 1) {
            $this->reject('Pilih atau unggah berkas dokumen yang valid.');
        }
        app(MediaReferenceCoordinator::class)->lockReferences([(int) $selectedId]);
        $media = Media::findOrFail((int) $selectedId);
        if (! app(DocumentDeliveryService::class)->resolveMedia($media)) {
            $this->reject('Berkas terpilih tidak memenuhi validasi dokumen.');
        }

        return (int) $selectedId;
    }

    private function reject(string $message = 'Berkas dokumen tidak valid.'): never
    {
        throw ValidationException::withMessages(['document_upload' => $message]);
    }
}
