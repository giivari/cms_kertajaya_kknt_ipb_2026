<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeHeaderWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.welcome-header-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';
}
