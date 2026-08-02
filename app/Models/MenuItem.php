<?php

namespace App\Models;

use App\Enums\LinkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'link_type',
        'page_id',
        'custom_url',
        'target',
        'position',
        'is_visible',
    ];

    protected $casts = [
        'link_type' => LinkType::class,
        'is_visible' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (MenuItem $item) {
            if ($item->link_type !== LinkType::CUSTOM) {
                return;
            }

            $url = $item->custom_url;
            $scheme = is_string($url) ? parse_url($url, PHP_URL_SCHEME) : null;

            if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
                throw ValidationException::withMessages([
                    'custom_url' => 'Alamat tautan luar harus menggunakan URL http atau https yang valid.',
                ]);
            }
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('position');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getUrlAttribute(): string
    {
        return match ($this->link_type) {
            LinkType::PAGE => $this->page ? route('pages.show', $this->page->slug) : '#',
            LinkType::HOME => route('home'),
            LinkType::NEWS_INDEX => route('news.index'),
            LinkType::GALLERY_INDEX => route('gallery.index'),
            LinkType::DOCUMENT_INDEX => route('documents.index'),
            LinkType::MAP => route('public.map.index'),
            LinkType::CONTACT => route('public.contact.show'),
            LinkType::CUSTOM => $this->custom_url ?? '#',
            default => '#',
        };
    }
}
