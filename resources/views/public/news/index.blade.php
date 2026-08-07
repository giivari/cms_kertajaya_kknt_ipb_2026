@extends('layouts.public')

@section('title', 'Berita Desa')

@section('content')
<div class="container mx-auto px-4 py-4">
    <h1 class="text-3xl font-bold mb-6">Berita Desa</h1>

    @if($news->isEmpty())
        <p class="text-gray-500">Belum ada berita yang dipublikasikan.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($news as $item)
                <div class="bg-white rounded-lg shadow overflow-hidden flex flex-col">
                    @if($item->featuredMedia)
                        <img src="{{ $item->featuredMedia->url }}" alt="{{ $item->featuredMedia->alt_text ?? $item->title }}" class="w-full h-48 object-cover">
                    @else
                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-500">
                            Tidak ada gambar
                        </div>
                    @endif
                    <div class="p-4 flex flex-col flex-grow">
                        @if($item->category)
                            <span class="text-sm text-blue-600 mb-2">{{ $item->category->name }}</span>
                        @endif
                        <h2 class="text-xl font-bold mb-2">
                            <a href="{{ route('news.show', $item->slug) }}" class="hover:text-blue-600">{{ $item->title }}</a>
                        </h2>
                        <p class="text-gray-600 text-sm mb-4">{{ Str::limit($item->excerpt, 100) }}</p>
                        <div class="mt-auto text-sm text-gray-500 flex justify-between">
                            <span>{{ $item->published_at->format('d M Y') }}</span>
                            <a href="{{ route('news.show', $item->slug) }}" class="text-blue-600 hover:underline">Baca &rarr;</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $news->links() }}
        </div>
    @endif
</div>
@endsection

