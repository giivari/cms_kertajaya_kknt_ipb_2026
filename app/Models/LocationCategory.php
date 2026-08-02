<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LocationCategory extends Model
{
    use Auditable, GeneratesUniqueSlug, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (LocationCategory $category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name, $category->getKey());
            }
        });

        static::deleting(function (LocationCategory $category) {
            if ($category->locations()->exists()) {
                throw new \Exception('Kategori lokasi tidak dapat dihapus karena masih digunakan.');
            }
        });
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
