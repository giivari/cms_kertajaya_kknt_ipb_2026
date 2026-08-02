<x-filament-widgets::widget>
    <div class="admin-stats-grid">
        @foreach ($stats as $stat)
            <article class="admin-stat-card">
                <div @class(['admin-stat-icon', 'admin-stat-icon--'.$stat['tone']])>
                    <x-filament::icon :icon="$stat['icon']" class="size-6" />
                </div>

                <div class="min-w-0">
                    <p class="admin-stat-value">{{ $stat['value'] }}</p>
                    <h2 class="admin-stat-label">{{ $stat['label'] }}</h2>
                    <p class="admin-stat-description">{{ $stat['description'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
