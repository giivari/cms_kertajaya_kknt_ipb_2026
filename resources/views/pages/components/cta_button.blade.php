<div class="my-6">
    @php
        $styleClass = match($data['style'] ?? 'primary') {
            'secondary' => 'bg-gray-100 text-gray-900 hover:bg-gray-200 border-transparent',
            'outline' => 'bg-transparent text-emerald-600 border-emerald-600 hover:bg-emerald-50',
            default => 'bg-emerald-600 text-white hover:bg-emerald-700 border-transparent'
        };
    @endphp
    
    <a href="{{ $data['url'] ?? '#' }}" class="inline-flex items-center justify-center px-6 py-3 border font-medium rounded-md shadow-sm transition-colors duration-200 {{ $styleClass }}">
        {{ $data['text'] ?? 'Click Here' }}
    </a>
</div>
