<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\Auditable;

class Document extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'download_count' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function fileMedia()
    {
        return $this->belongsTo(Media::class, 'file_media_id');
    }

    public function thumbnailMedia()
    {
        return $this->belongsTo(Media::class, 'thumbnail_media_id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')
              ->whereNotNull('published_at')
              ->where('published_at', '<=', now());
    }
}
