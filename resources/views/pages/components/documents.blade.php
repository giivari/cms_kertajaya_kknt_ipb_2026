@php
    $ids = collect($data['document_ids'] ?? [])->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)->map(fn ($id) => (int) $id)->unique();
    $documents = \App\Models\Document::published()->with('fileMedia')->whereKey($ids->all())->get()->keyBy('id');

    // Historical blocks store Media IDs. Adapt only a real, unique Document
    // relationship; never assume matching numeric primary keys mean the same file.
    if ($ids->isEmpty()) {
        foreach (collect($data['documents'] ?? [])->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)->unique() as $legacyMediaId) {
            $matches = \App\Models\Document::withTrashed()->where('file_media_id', (int) $legacyMediaId)->limit(2)->get();
            if ($matches->count() === 1 && $matches->first()->isPublished() && ! $matches->first()->trashed()) {
                $documents->put($matches->first()->id, $matches->first()->load('fileMedia'));
            }
        }
    }
    $delivery = app(\App\Services\DocumentDeliveryService::class);
    $available = $documents->filter(fn ($document) => $delivery->resolve($document) !== null);
    $previewOnly = ($isPreview ?? false) ? \App\Models\Document::query()
        ->whereKey($ids->all())->get()->keyBy('id')->diffKeys($available) : collect();
@endphp

@if($available->isNotEmpty())
    <ul class="divide-y divide-gray-200 border-t border-b border-gray-200 my-8">
        @foreach($available as $document)
            <li class="py-4 flex items-center justify-between hover:bg-gray-50 px-4 transition-colors rounded-md">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-rose-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span class="text-sm font-medium text-gray-900">{{ $document->title }}</span>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <a href="{{ route('documents.download', $document->slug) }}" class="font-medium text-emerald-600 hover:text-emerald-500 text-sm">Download</a>
                </div>
            </li>
        @endforeach
    </ul>
@endif

@if(($isPreview ?? false) && $previewOnly->isNotEmpty())
    <ul class="divide-y divide-gray-200 border my-8">
        @foreach($previewOnly as $document)
            <li class="px-4 py-3 text-gray-700">{{ $document->title }} <span class="text-sm text-gray-500">(belum tersedia untuk unduhan publik)</span></li>
        @endforeach
    </ul>
@endif
