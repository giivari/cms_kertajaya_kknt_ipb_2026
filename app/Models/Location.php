<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Traits\HasContentLifecycle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Location extends Model
{
    use HasContentLifecycle, Auditable, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Location $location) {
            $errors = [];

            if (! is_numeric($location->latitude) || (float) $location->latitude < -90 || (float) $location->latitude > 90) {
                $errors['latitude'] = 'Garis lintang harus berada di antara -90 dan 90.';
            }

            if (! is_numeric($location->longitude) || (float) $location->longitude < -180 || (float) $location->longitude > 180) {
                $errors['longitude'] = 'Garis bujur harus berada di antara -180 dan 180.';
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(LocationCategory::class, 'location_category_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->published()
            ->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('is_active', true));
    }

    public function isPubliclyVisible(): bool
    {
        return static::query()->publiclyVisible()->whereKey($this->getKey())->exists();
    }
}
