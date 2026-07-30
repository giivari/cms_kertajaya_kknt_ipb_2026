<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -1;

    public static function getNavigationGroup(): ?string
    {
        return 'UTAMA';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dasbor';
    }
}
