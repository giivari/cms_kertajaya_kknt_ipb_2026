<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'component_type',
        'column_position',
        'position',
        'content_data',
        'component_settings',
        'is_visible',
    ];

    protected $casts = [
        'content_data' => 'array',
        'component_settings' => 'array',
        'is_visible' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(PageSection::class, 'section_id');
    }
}
