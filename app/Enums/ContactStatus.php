<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ContactStatus: string implements HasLabel, HasColor
{
    case NEW = 'new';
    case READ = 'read';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEW => 'Baru',
            self::READ => 'Dibaca',
            self::IN_PROGRESS => 'Diproses',
            self::RESOLVED => 'Selesai',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW => 'danger',
            self::READ => 'warning',
            self::IN_PROGRESS => 'info',
            self::RESOLVED => 'success',
        };
    }
}