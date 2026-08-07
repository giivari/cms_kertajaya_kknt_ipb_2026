@php
    $villageName = \App\Services\SettingsService::get('village_name', 'Desa Kertajaya');
    $logoId = \App\Services\SettingsService::get('village_logo');
    $logoUrl = null;
    if ($logoId) {
        try {
            $media = \App\Models\Media::find($logoId);
            if ($media && $media->invisible_watermark_status?->value === 'verified') {
                $deriv = $media->getPublicDerivative('thumbnail');
                if ($deriv) $logoUrl = Storage::disk('public')->url($deriv->filename);
            }
        } catch (\Exception $e) {}
    }
@endphp
<div class="fi-auth-theme-switcher">
    <x-filament-panels::theme-switcher />
</div>
<div class="admin-auth-brand flex flex-col items-center justify-center space-y-4 mt-8 sm:mt-10 pt-4 pb-2">
    @if($logoUrl)
        <div class="admin-auth-brand-mark flex items-center justify-center w-16 h-16 bg-white dark:bg-gray-800 rounded-full shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20">
            <img src="{{ $logoUrl }}" alt="{{ $villageName }}" class="h-10 w-10 object-contain">
        </div>
    @else
        <div class="admin-auth-brand-mark flex items-center justify-center w-14 h-14 rounded-full bg-teal-500/10 text-teal-600 dark:bg-teal-400/10 dark:text-teal-400 ring-1 ring-teal-500/20 dark:ring-teal-400/20">
            @svg('heroicon-s-shield-check', 'w-8 h-8')
        </div>
    @endif
    
    <div class="flex flex-col items-center text-center space-y-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            CMS {{ $villageName }}
        </h1>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
            Sistem Pengelolaan Website Desa
        </p>
    </div>
</div>
