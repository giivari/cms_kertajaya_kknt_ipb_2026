@php
    $documentIds = $data['documents'] ?? [];
    $mediaItems = !empty($documentIds) ? \App\Models\Media::whereIn('id', $documentIds)->get() : [];
@endphp

@if($mediaItems->count() > 0)
    <ul class="divide-y divide-gray-200 border-t border-b border-gray-200 my-8">
        @foreach($mediaItems as $media)
            <li class="py-4 flex items-center justify-between hover:bg-gray-50 px-4 transition-colors rounded-md">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-rose-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span class="text-sm font-medium text-gray-900">{{ $media->original_filename }}</span>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <a href="{{ Storage::url($media->file_path) }}" target="_blank" class="font-medium text-emerald-600 hover:text-emerald-500 text-sm">Download</a>
                </div>
            </li>
        @endforeach
    </ul>
@endif
