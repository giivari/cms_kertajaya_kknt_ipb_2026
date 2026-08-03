<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardLayoutWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dashboard-layout-widget';
}
