@php
    $imageIds = $data['images'] ?? [];
    $mediaItems = !empty($imageIds) ? \App\Models\Media::whereIn('id', $imageIds)->get() : collect();
@endphp

@if($mediaItems->count() > 0)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 my-6">
        @foreach($mediaItems as $media)
            @php
                $deriv = $media->getPublicDerivative('large') ?? $media->getPublicDerivative('small');
                if (!$deriv) continue;
                $url = \Illuminate\Support\Facades\Storage::disk('public')->url($deriv->filename);
            @endphp
            <a href="{{ $url }}" target="_blank" class="block group relative overflow-hidden rounded-lg aspect-square bg-gray-100">
                <img src="{{ $url }}" alt="{{ $media->original_filename }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300"></div>
            </a>
        @endforeach
    </div>
@endif
