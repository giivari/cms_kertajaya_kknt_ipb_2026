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
<div class="admin-auth-brand flex flex-col items-center justify-center mt-8 sm:mt-10 pt-4 pb-2" style="gap: 1.5rem;">
    @if($logoUrl)
        <div class="admin-auth-brand-mark flex items-center justify-center rounded-full ring-1 ring-gray-950/10" style="width: 6rem; height: 6rem; overflow: hidden; flex-shrink: 0; background-color: #ffffff; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);">
            <img src="{{ $logoUrl }}" alt="{{ $villageName }}" style="width: 4.5rem; height: 4.5rem; object-fit: contain; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
        </div>
    @else
        <div class="admin-auth-brand-mark flex items-center justify-center rounded-full text-teal-600 ring-1 ring-teal-500/20" style="width: 6rem; height: 6rem; flex-shrink: 0; background-color: #ffffff; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);">
            @svg('heroicon-s-shield-check', 'w-8 h-8', ['style' => 'width: 3rem; height: 3rem;'])
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
