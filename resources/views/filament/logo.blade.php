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
<div class="flex items-center gap-2 max-w-full overflow-hidden">
    @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $villageName }}" class="h-8 w-8 object-contain rounded shrink-0" />
    @endif
    <span class="font-bold text-lg leading-none truncate text-gray-900 tracking-tight">
        CMS {{ $villageName }}
    </span>
</div>
