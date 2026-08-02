<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreviewToken extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'payload_bytes' => 'integer',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
