<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'featured_media_id',
        'status',
        'is_featured',
        'seo_title',
        'seo_description',
        'published_at',
    ];

    protected $casts = [
        'status' => PageStatus::class,
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('position');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }

            $originalSlug = $page->slug;
            $count = 1;
            while (static::where('slug', $page->slug)->where('id', '!=', $page->id)->exists()) {
                $page->slug = "{$originalSlug}-{$count}";
                $count++;
            }
        });
    }
}
