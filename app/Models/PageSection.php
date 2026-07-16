<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'name',
        'layout_type',
        'position',
        'section_settings',
        'is_visible',
    ];

    protected $casts = [
        'section_settings' => 'array',
        'is_visible' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(PageComponent::class)->orderBy('position');
    }
}
