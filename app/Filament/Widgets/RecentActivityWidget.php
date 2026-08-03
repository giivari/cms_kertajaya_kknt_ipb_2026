<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Widgets\Widget;

class RecentActivityWidget extends Widget
{
    

    protected string $view = 'filament.widgets.recent-activity-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    protected function getViewData(): array
    {
        return [
            'activities' => AuditLog::query()
                ->with('admin')
                ->latest('created_at')
                ->limit(5)
                ->get(),
        ];
    }
}
