<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsCategory extends Model
{
    use Auditable, GeneratesUniqueSlug, HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->name, $model->getKey());
            }
        });

        static::deleting(function ($model) {
            if ($model->news()->count() > 0) {
                throw new \Exception('Cannot delete category because it is referenced by news items.');
            }
        });
    }

    protected $guarded = [];

    public function news()
    {
        return $this->hasMany(News::class);
    }
}
