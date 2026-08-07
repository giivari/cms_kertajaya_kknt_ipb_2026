<?php

namespace App\Models;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Services\MediaDeletionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'disk', 'directory', 'filename', 'original_filename',
        'mime_type', 'extension', 'size', 'width', 'height',
        'alt_text', 'caption', 'metadata', 'checksum', 'uploaded_at',
        'processing_status', 'invisible_watermark_status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'uploaded_at' => 'datetime',
        'processing_status' => MediaProcessingStatus::class,
        'invisible_watermark_status' => InvisibleWatermarkStatus::class,
    ];

    public function derivatives()
    {
        return $this->hasMany(MediaDerivative::class);
    }

    public function verificationLogs()
    {
        return $this->hasMany(WatermarkVerificationLog::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Media $media) {
            if ($media->isForceDeleting()) {
                return;
            }

            $deletionService = app(MediaDeletionService::class);
            $deletionService->validateDeletion($media);
        });
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('processing_status', MediaProcessingStatus::COMPLETED->value)
            ->where('invisible_watermark_status', InvisibleWatermarkStatus::VERIFIED->value)
            ->whereHas('derivatives', function ($q) {
                $q->where('derivative_type', 'public');
            });
    }

    public function scopeApprovedImages(Builder $query): void
    {
        $query->approved()->where('mime_type', 'like', 'image/%');
    }

    public function scopeApprovedPdfs(Builder $query): void
    {
        $query->approved()->where('mime_type', 'application/pdf');
    }

    public function getUrlAttribute(): string
    {
        $path = \App\Filament\Support\MediaThumbnail::path($this);
        if ($path) {
            return Storage::disk(\App\Filament\Support\MediaThumbnail::disk($this))->url($path);
        }
        return Storage::disk($this->disk)->url($this->directory.'/'.$this->filename);
    }

    public function getPublicDerivative(string $size = 'public')
    {
        return $this->derivatives()->where('derivative_type', \App\Enums\DerivativeType::PUBLIC)->first();
    }
}
