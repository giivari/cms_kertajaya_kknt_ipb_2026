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

<div class="flex min-w-0 items-center gap-3">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $villageName }}" style="width: 2rem; height: 2rem; object-fit: contain;">
    @else
        <div class="flex items-center justify-center rounded-full bg-teal-600" style="width: 2rem; height: 2rem; flex-shrink: 0;">
            <svg class="admin-sidebar-brand-icon text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" style="width: 1.25rem; height: 1.25rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
        </div>
    @endif

    <span class="fi-brand-text min-w-0 truncate text-sm font-bold text-gray-950 dark:text-white">
        CMS {{ $villageName }}
    </span>
</div>
