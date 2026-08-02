<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -1;

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 3,
        ];
    }

    public function getHeading(): string
    {
        $admin = filament()->auth()->user();
        $name = $admin?->name ?: ($admin?->username ?: 'Administrator');

        return "Selamat Datang, {$name}";
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan aktivitas dan status website Desa Kertajaya hari ini.';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dasbor';
    }
}
