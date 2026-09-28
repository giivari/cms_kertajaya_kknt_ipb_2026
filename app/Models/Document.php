<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Document extends Model
{
    use \App\Traits\HasContentLifecycle, Auditable, HasFactory;
    use SoftDeletes { forceDelete as private softDeletesForceDelete; }

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

    public function forceDelete()
    {
        return DB::transaction(function () {
            app(\App\Services\DocumentReferenceCoordinator::class)->lockForDeletion((int) $this->getKey());

            return $this->softDeletesForceDelete();
        });
    }
}
