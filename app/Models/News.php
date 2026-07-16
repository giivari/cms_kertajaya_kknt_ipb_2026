<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory, SoftDeletes, Auditable, \App\Traits\HasContentLifecycle;

    protected $guarded = [];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function featuredMedia()
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }


}
