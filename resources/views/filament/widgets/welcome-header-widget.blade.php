<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $admin = filament()->auth()->user();
            $name = $admin?->name ?: ($admin?->username ?: 'Administrator');
            $initial = mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: 'A';
        @endphp
        <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-teal-700 text-lg font-semibold text-white shadow-sm">
                {{ $initial }}
            </div>
            <div class="min-w-0">
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Selamat datang, {{ $name }}!
                </h2>
                <p class="mt-1 text-sm font-medium text-teal-700 dark:text-teal-300">Administrator Desa</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Berikut adalah ringkasan aktivitas CMS Website Desa Kertajaya hari ini.
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
