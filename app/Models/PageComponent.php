<?php

namespace App\Models;

use App\Enums\ComponentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_section_id',
        'component_type',
        'column_position',
        'position',
        'content_data',
        'component_settings',
        'is_visible',
    ];

    protected $casts = [
        'component_type' => ComponentType::class,
        'content_data' => 'array',
        'component_settings' => 'array',
        'is_visible' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }
}
