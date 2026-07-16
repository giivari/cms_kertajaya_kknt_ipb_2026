<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatermarkVerificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_id',
        'is_verified',
        'details',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'details' => 'array',
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
