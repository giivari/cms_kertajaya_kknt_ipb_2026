@if(!empty($data['cards']))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 my-8">
        @foreach($data['cards'] as $card)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
                <div class="p-6 flex-grow">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $card['title'] ?? '' }}</h3>
                    @if(!empty($card['description']))
                        <p class="text-gray-600 mb-4">{{ $card['description'] }}</p>
                    @endif
                </div>
                @if(!empty($card['link_url']))
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 mt-auto">
                        <a href="{{ $card['link_url'] }}" class="text-emerald-600 font-medium hover:text-emerald-700 text-sm flex items-center">
                            Selengkapnya 
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
