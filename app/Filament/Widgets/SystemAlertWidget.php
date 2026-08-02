<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SystemAlertWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = "filament.widgets.system-alert-widget";

    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = "full";
}
