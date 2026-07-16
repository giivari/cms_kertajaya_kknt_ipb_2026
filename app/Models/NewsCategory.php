<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NewsCategory extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $slug = Str::slug($model->name);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $originalSlug.'-'.$count++;
                }
                $model->slug = $slug;
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
