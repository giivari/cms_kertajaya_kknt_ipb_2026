@extends('layouts.public')

@section('title', 'Galeri Desa')

@section('content')
@include('partials.page-header', [
    'title' => 'Galeri Desa',
    'description' => 'Dokumentasi kegiatan dan potensi yang ada di desa',
    'breadcrumbs' => [
        ['label' => 'Beranda', 'url' => route('home')],
        ['label' => 'Galeri', 'url' => null]
    ]
])

<div class="w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32 py-12">

    @if($albums->isEmpty())
        <p class="text-gray-500">Belum ada galeri yang dipublikasikan.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($albums as $album)
                <a href="{{ route('gallery.show', $album->slug) }}" class="group block">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden relative">
                        @if($album->coverMedia)
                            <img src="{{ $album->coverMedia->url }}" alt="{{ $album->title }}" class="w-full h-48 object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-500">
                                Tidak ada gambar
                            </div>
                        @endif
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                            <h2 class="text-white font-bold text-lg">{{ $album->title }}</h2>
                            <p class="text-gray-200 text-sm">{{ $album->published_at->format('d M Y') }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $albums->links() }}
        </div>
    @endif
</div>
@endsection

