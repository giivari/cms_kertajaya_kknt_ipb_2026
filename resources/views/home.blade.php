@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
<div class="relative bg-emerald-700 text-white overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-800 to-emerald-600 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-black/40"></div>
    </div>
    <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8 flex flex-col items-center text-center">
        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-white drop-shadow-md">
            Selamat Datang di <span class="text-emerald-300">Desa</span>
        </h1>
        <p class="mt-6 text-xl max-w-3xl text-gray-100 drop-shadow">
            Portal informasi dan pelayanan masyarakat terpadu. Wujud transparansi dan komitmen kami untuk membangun desa yang lebih baik.
        </p>
        <div class="mt-10 max-w-sm mx-auto sm:max-w-none sm:flex sm:justify-center gap-4">
            <a href="#" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-emerald-700 bg-white hover:bg-gray-50 shadow-sm transition-colors duration-200">
                Profil Desa
            </a>
            <a href="#" class="mt-3 sm:mt-0 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-emerald-600 bg-opacity-60 hover:bg-opacity-70 border-emerald-400 shadow-sm transition-colors duration-200">
                Layanan Publik
            </a>
        </div>
    </div>
</div>

<div class="bg-white py-16 sm:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Layanan Kami</h2>
            <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">Kami menyediakan berbagai layanan administrasi dan informasi untuk memudahkan kebutuhan masyarakat.</p>
        </div>

        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-md transition-shadow">
                <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Administrasi Surat</h3>
                <p class="text-gray-500 text-sm">Pelayanan pembuatan surat keterangan, pengantar, dan administrasi kependudukan lainnya.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-md transition-shadow">
                <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Berita & Pengumuman</h3>
                <p class="text-gray-500 text-sm">Informasi terkini seputar kegiatan desa, program pemerintah, dan pengumuman penting.</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-md transition-shadow">
                <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Potensi Desa</h3>
                <p class="text-gray-500 text-sm">Pengembangan potensi ekonomi lokal, UMKM, pariwisata, dan pemberdayaan masyarakat.</p>
            </div>
        </div>
    </div>
</div>

@if(isset($latestNews) && $latestNews->isNotEmpty())
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Berita Terkini</h2>
            <a href="{{ route('news.index') }}" class="text-emerald-600 hover:text-emerald-700 font-medium">Lihat Semua &rarr;</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($latestNews as $news)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden flex flex-col">
                    @if($news->featuredMedia)
                        <img src="{{ $news->featuredMedia->url }}" class="w-full h-48 object-cover">
                    @else
                        <div class="w-full h-48 bg-gray-200"></div>
                    @endif
                    <div class="p-6 flex-grow flex flex-col">
                        <span class="text-sm text-emerald-600 mb-2 font-medium">{{ $news->category?->name }}</span>
                        <h3 class="text-xl font-bold mb-2"><a href="{{ route('news.show', $news->slug) }}" class="hover:text-emerald-700">{{ $news->title }}</a></h3>
                        <p class="text-gray-500 text-sm mb-4">{{ Str::limit($news->excerpt, 100) }}</p>
                        <div class="mt-auto text-sm text-gray-400">
                            {{ $news->published_at->format('d M Y') }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(isset($latestAlbums) && $latestAlbums->isNotEmpty())
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Galeri Kegiatan</h2>
            <a href="{{ route('gallery.index') }}" class="text-emerald-600 hover:text-emerald-700 font-medium">Lihat Semua &rarr;</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($latestAlbums as $album)
                <a href="{{ route('gallery.show', $album->slug) }}" class="group block relative rounded-lg overflow-hidden h-48">
                    @if($album->coverMedia)
                        <img src="{{ $album->coverMedia->url }}" class="w-full h-full object-cover transition duration-300 group-hover:scale-110">
                    @else
                        <div class="w-full h-full bg-gray-200"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-4">
                        <h3 class="text-white font-bold">{{ $album->title }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(isset($latestDocuments) && $latestDocuments->isNotEmpty())
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Dokumen Publik</h2>
            <a href="{{ route('documents.index') }}" class="text-emerald-600 hover:text-emerald-700 font-medium">Lihat Semua &rarr;</a>
        </div>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @foreach($latestDocuments as $doc)
                    <li>
                        <div class="px-4 py-4 flex items-center sm:px-6">
                            <div class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between">
                                <div class="truncate">
                                    <div class="flex text-sm">
                                        <p class="font-medium text-emerald-600 truncate">{{ $doc->title }}</p>
                                    </div>
                                    <div class="mt-2 flex">
                                        <div class="flex items-center text-sm text-gray-500">
                                            {{ $doc->category?->name }} &bull; {{ $doc->published_at->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-5 flex-shrink-0">
                                <a href="{{ route('documents.download', $doc->slug) }}" target="_blank" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">Unduh</a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

@endsection
