@if(!empty($data['items']))
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 my-8">
        @foreach($data['items'] as $item)
            <div class="text-center p-6 bg-emerald-50 rounded-xl border border-emerald-100 shadow-sm">
                @if(!empty($item['icon']))
                    <div class="text-3xl mb-3 text-emerald-600">{{ $item['icon'] }}</div>
                @endif
                <div class="text-3xl font-extrabold text-gray-900">{{ $item['value'] ?? '' }}</div>
                <div class="text-sm font-medium text-gray-500 mt-1 uppercase tracking-wide">{{ $item['label'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
@endif
