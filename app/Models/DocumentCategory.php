<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\Auditable;

class DocumentCategory extends Model
{
    use HasFactory, Auditable;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $slug = \Illuminate\Support\Str::slug($model->name);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $model->slug = $slug;
            }
        });
    }

    protected $guarded = [];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
