<?php

namespace App\Models;

use App\Enums\InvisibleWatermarkStatus;
use App\Enums\DerivativeType;
use App\Enums\MediaProcessingStatus;
use App\Services\MediaDeletionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'disk', 'directory', 'filename', 'original_filename',
        'mime_type', 'extension', 'size', 'width', 'height',
        'alt_text', 'caption', 'metadata', 'checksum', 'uploaded_at',
        'processing_status', 'invisible_watermark_status',
        'processing_token', 'last_processing_status', 'last_processing_error',
        'cleanup_status', 'cleanup_error', 'cleanup_started_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'uploaded_at' => 'datetime',
        'processing_status' => MediaProcessingStatus::class,
        'invisible_watermark_status' => InvisibleWatermarkStatus::class,
        'cleanup_started_at' => 'datetime',
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
        static::updating(function (Media $media): void {
            if ($media->isDirty(['disk', 'directory', 'filename'])) {
                // Invalidate any processing job that captured the previous source.
                $media->processing_token = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::deleting(function (Media $media) {
            $deletionService = app(MediaDeletionService::class);
            $deletionService->validateDeletion($media);
            if ($media->isForceDeleting() && $media->cleanup_status !== 'completed') {
                throw new \RuntimeException('Permanent deletion must complete tracked-file cleanup first.');
            }
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
        $query->approved()->whereHas('derivatives', fn (Builder $derivative) => $derivative
            ->where('derivative_type', 'public')->where('mime_type', 'like', 'image/%'));
    }

    public function scopeApprovedPdfs(Builder $query): void
    {
        $query->approved()->whereHas('derivatives', fn (Builder $derivative) => $derivative
            ->where('derivative_type', 'public')->where('mime_type', 'application/pdf'));
    }

    public function getUrlAttribute(): string
    {
        $url = app(\App\Services\MediaDeliveryService::class)->url($this);
        if ($url) {
            return $url;
        }
        // An unavailable derivative must never fall back to an original URL.
        return \App\Filament\Support\MediaThumbnail::placeholderUrl($this->mime_type);
    }

    public function getPublicDerivative(string $size = 'public')
    {
        $type = $size === 'thumbnail' ? DerivativeType::THUMBNAIL : DerivativeType::PUBLIC;
        if ($this->relationLoaded('derivatives')) {
            return $this->derivatives->first(fn (MediaDerivative $derivative): bool =>
                $derivative->derivative_type === $type)
                ?? ($type === DerivativeType::THUMBNAIL ? $this->derivatives->first(fn (MediaDerivative $derivative): bool =>
                    $derivative->derivative_type === DerivativeType::PUBLIC) : null);
        }

        return $this->derivatives()->where('derivative_type', $type)->first()
            ?? ($type === DerivativeType::THUMBNAIL ? $this->derivatives()->where('derivative_type', DerivativeType::PUBLIC)->first() : null);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return app(\App\Services\MediaDeliveryService::class)->url($this, DerivativeType::THUMBNAIL) ?? $this->url;
    }
}
