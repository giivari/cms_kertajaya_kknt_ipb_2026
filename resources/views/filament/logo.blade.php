@php
    $logoId = \App\Services\SettingsService::get('village_logo');
    $logoUrl = null;
    if ($logoId) {
        try {
            $media = \App\Models\Media::find($logoId);
            if ($media && $media->invisible_watermark_status === 'verified') {
                $derivative = $media->getPublicDerivative('thumbnail');
                if ($derivative) {
                    $logoUrl = Storage::disk('public')->url($derivative->file_path);
                }
            }
        } catch (\Exception $e) {}
    }
    
    $villageName = \App\Services\SettingsService::get('village_name', 'Desa Kertajaya');
@endphp
<div class="flex items-center gap-3 py-2">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $villageName }} Logo" class="h-10 w-auto object-contain rounded-md" />
    @else
        <div class="h-10 w-10 bg-primary-600 text-white rounded-md flex items-center justify-center font-bold text-xl uppercase shadow-sm">
            {{ Str::substr($villageName, 0, 1) }}
        </div>
    @endif
    <div class="flex flex-col">
        <span class="font-bold text-lg leading-tight tracking-tight text-gray-900 dark:text-white">CMS {{ $villageName }}</span>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Sistem Pengelolaan Website Desa</span>
    </div>
</div>
