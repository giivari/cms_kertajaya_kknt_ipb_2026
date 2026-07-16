<{{ $data['level'] ?? 'h2' }} class="font-bold text-gray-900 
    {{ ($data['level'] ?? 'h2') === 'h1' ? 'text-4xl lg:text-5xl' : '' }}
    {{ ($data['level'] ?? 'h2') === 'h2' ? 'text-3xl' : '' }}
    {{ ($data['level'] ?? 'h2') === 'h3' ? 'text-2xl' : '' }}
    {{ ($data['level'] ?? 'h2') === 'h4' ? 'text-xl' : '' }}
    text-{{ $data['alignment'] ?? 'left' }}
">
    {{ $data['text'] ?? '' }}
</{{ $data['level'] ?? 'h2' }}>
