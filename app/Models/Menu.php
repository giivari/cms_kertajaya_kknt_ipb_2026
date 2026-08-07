<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
    ];

    public const HEADER = 'primary';

    public const FOOTER = 'footer_menu';

    public static function supportedLocations(): array
    {
        return [
            self::HEADER => 'Navigasi Utama',
            self::FOOTER => 'Kaki Halaman',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Menu $menu) {
            $locations = static::supportedLocations();

            if (! array_key_exists($menu->location, $locations)) {
                throw ValidationException::withMessages([
                    'location' => 'Pilih lokasi tampilan yang tersedia.',
                ]);
            }

            $menu->name = $locations[$menu->location];
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('position');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('position');
    }
}
