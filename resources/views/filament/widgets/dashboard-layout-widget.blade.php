<x-filament-widgets::widget>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            @livewire(\App\Filament\Widgets\QuickActionsWidget::class)
            @livewire(\App\Filament\Widgets\SystemAlertWidget::class)
        </div>
        <div class="lg:col-span-2">
            @livewire(\App\Filament\Widgets\RecentActivityWidget::class)
        </div>
    </div>
</x-filament-widgets::widget>
