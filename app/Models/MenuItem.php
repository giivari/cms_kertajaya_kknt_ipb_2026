<?php

namespace App\Models;

use App\Enums\LinkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            LinkType::PAGE => $this->page ? url('/halaman/'.$this->page->slug) : '#',
            LinkType::NEWS_INDEX => route('news.index'),
            LinkType::GALLERY_INDEX => route('gallery.index'),
            LinkType::DOCUMENT_INDEX => route('documents.index'),
            LinkType::MAP => url('/peta'),
            LinkType::CONTACT => url('/narahubung'),
            LinkType::CUSTOM => $this->custom_url ?? '#',
            default => '#',
        };
    }
}
