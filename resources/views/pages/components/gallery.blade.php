@php
    $imageIds = $data['images'] ?? [];
    $mediaItems = !empty($imageIds) ? \App\Models\Media::whereIn('id', $imageIds)->get() : [];
@endphp

@if($mediaItems->count() > 0)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 my-6">
        @foreach($mediaItems as $media)
            <a href="{{ Storage::url($media->filename) }}" target="_blank" class="block group relative overflow-hidden rounded-lg aspect-square bg-gray-100">
                <img src="{{ Storage::url($media->filename) }}" alt="{{ $media->original_filename }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-300">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300"></div>
            </a>
        @endforeach
    </div>
@endif
