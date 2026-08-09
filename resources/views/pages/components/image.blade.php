@php
    $media = isset($data['media_id']) ? \App\Models\Media::find($data['media_id']) : null;
@endphp

@if($media)
    <figure class="my-6">
        <img src="{{ Storage::url($media->filename) }}" alt="{{ $data['alt_text'] ?? $media->original_filename }}" class="rounded-lg shadow-sm w-full h-auto">
        @if(!empty($data['caption']))
            <figcaption class="mt-2 text-center text-sm text-gray-500">{{ $data['caption'] }}</figcaption>
        @endif
    </figure>
@endif
