@extends('layouts.public')

@section('title', $newsItem->seo_title ?: $newsItem->title)
@section('seo_description')
{{ $newsItem->seo_description ?: \Illuminate\Support\Str::limit($newsItem->excerpt, 150) }}
@endsection

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('news.index') }}" class="text-blue-600 hover:underline">&larr; Kembali ke Berita</a>
    </div>

    <h1 class="text-4xl font-bold mb-4">{{ $newsItem->title }}</h1>
    
    <div class="flex items-center text-gray-500 mb-6 space-x-4">
        <span>{{ $newsItem->published_at->format('d F Y') }}</span>
        @if($newsItem->category)
            <span class="bg-gray-200 px-2 py-1 rounded text-sm text-gray-700">{{ $newsItem->category->name }}</span>
        @endif
    </div>

    @if($newsItem->featuredMedia)
        <div class="mb-8 rounded-lg overflow-hidden shadow-lg">
            <img src="{{ $newsItem->featuredMedia->url }}" alt="{{ $newsItem->featuredMedia->alt_text ?? $newsItem->title }}" class="w-full h-auto">
            @if($newsItem->featuredMedia->caption)
                <p class="text-center text-sm text-gray-500 mt-2 p-2">{{ $newsItem->featuredMedia->caption }}</p>
            @endif
        </div>
    @endif

    <div class="prose max-w-none">
        {!! $newsItem->content !!}
    </div>
</div>
@endsection

