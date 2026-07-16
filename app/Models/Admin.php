<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable implements FilamentUser, HasAppAuthentication
{
    use Auditable, HasFactory, HasUuids, InteractsWithAppAuthentication, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'force_password_change',
        'password_changed_at',
        'app_authentication_secret',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'app_authentication_secret' => 'encrypted',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admin) {
            if (static::count() > 0) {
                throw new \Exception('Only one administrator account is permitted.');
            }
        });

        static::deleting(function ($admin) {
            if (static::count() <= 1) {
                throw new \Exception('Cannot delete the sole administrator account.');
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
