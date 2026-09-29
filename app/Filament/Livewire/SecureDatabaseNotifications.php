<?php

namespace App\Filament\Livewire;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Livewire\DatabaseNotifications;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class SecureDatabaseNotifications extends DatabaseNotifications
{
    public function mount(): void
    {
        $this->getMfaAuthenticatedUser();
    }

    public function getUser(): Model | Authenticatable | null
    {
        return $this->getMfaAuthenticatedUser();
    }

    private function getMfaAuthenticatedUser(): Model | Authenticatable
    {
        $user = parent::getUser();

        abort_unless($user && AppAuthentication::make()->isEnabled($user), 403);

        return $user;
    }
}
