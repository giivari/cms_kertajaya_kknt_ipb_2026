<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasContentLifecycle
{
    public static function bootHasContentLifecycle()
    {
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = 'draft';
            }
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->title ?? $model->name);
            }
            if ($model->status === 'published' && empty($model->published_at)) {
                $model->published_at = now();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                if ($model->status === 'published' && empty($model->published_at)) {
                    $model->published_at = now();
                }
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at <= now();
    }
}
