@extends('layouts.public')

@section('title', $album->title . ' - Galeri')
@section('seo_description')
{{ \Illuminate\Support\Str::limit($album->description, 150) }}
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('gallery.index') }}" class="text-blue-600 hover:underline">&larr; Kembali ke Galeri</a>
    </div>

    <h1 class="text-3xl font-bold mb-2">{{ $album->title }}</h1>
    <p class="text-gray-500 mb-6">{{ $album->published_at ? $album->published_at->format('d F Y') : 'Draft' }}</p>
    
    @if($album->description)
        <div class="mb-8 text-gray-700">
            {{ $album->description }}
        </div>
    @endif

    @if($album->items->isEmpty())
        <p class="text-gray-500">Belum ada foto dalam album ini.</p>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($album->items as $item)
                @if($item->media)
                    <div class="relative group rounded-lg overflow-hidden shadow">
                        <img src="{{ $item->media->url }}" alt="{{ $item->alt_text ?? $item->caption }}" class="w-full h-48 object-cover transition duration-300 group-hover:scale-105">
                        @if($item->caption)
                            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-sm p-2 opacity-0 group-hover:opacity-100 transition duration-300">
                                {{ $item->caption }}
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection

