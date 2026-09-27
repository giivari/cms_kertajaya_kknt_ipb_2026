<?php

namespace App\Models;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\GeneratesUniqueSlug;

class Page extends Model
{
    use GeneratesUniqueSlug, HasFactory, SoftDeletes;

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

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PageStatus::PUBLISHED->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now())
            && ! $this->trashed();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($page) {
            if (empty($page->slug)) {
                $page->slug = static::generateUniqueSlug($page->title, $page->getKey());
            }

            if ($page->status === PageStatus::PUBLISHED && $page->published_at === null && (
                ! $page->exists || $page->isDirty('status')
            )) {
                $page->published_at = now();
            }
        });

        static::deleted(fn (Page $page) => \App\Services\AuditLogService::log(
            $page->isForceDeleting() ? 'page_permanently_deleted' : 'page_archived', $page,
        ));
        static::restored(fn (Page $page) => \App\Services\AuditLogService::log('page_restored', $page));
    }
}
