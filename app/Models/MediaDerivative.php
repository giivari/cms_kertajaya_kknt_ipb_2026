<?php

namespace App\Models;

use App\Enums\DerivativeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaDerivative extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_id',
        'derivative_type',
        'filename',
        'disk',
        'size',
        'mime_type',
        'width',
        'height',
        'checksum',
    ];

    protected $casts = [
        'derivative_type' => DerivativeType::class,
    ];

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
