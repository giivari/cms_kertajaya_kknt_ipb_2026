@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
<!-- HERO SECTION -->
<div class="relative bg-navy-900 overflow-hidden">
    <div class="absolute inset-0">
        <!-- Replace with dynamic hero image from settings if available, or keep abstract -->
        <div class="absolute inset-0 bg-gradient-to-r from-teal-900 to-navy-900 opacity-90 mix-blend-multiply"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8 flex flex-col items-center text-center">
        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl font-display">
            Selamat Datang di <span class="text-lime-400">{{ \App\Services\SettingsService::get('village_name', 'Desa Kertajaya') }}</span>
        </h1>
        <p class="mt-6 max-w-3xl text-xl text-cream-100">
            {{ \App\Services\SettingsService::get('village_description', 'Membangun masyarakat yang sejahtera, mandiri, dan berbudaya melalui tata kelola desa yang transparan dan inovatif.') }}
        </p>
        <div class="mt-10 flex gap-4 justify-center">
            <a href="/news" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-navy-900 bg-lime-400 hover:bg-lime-300 transition-colors duration-200">
                Kabar Desa
            </a>
            <a href="/documents" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-teal-600 hover:bg-teal-500 transition-colors duration-200 shadow-sm">
                Layanan Publik
            </a>
        </div>
    </div>
    
    <!-- Decorative bottom wave -->
    <div class="absolute bottom-0 w-full overflow-hidden leading-none">
        <svg class="relative block w-full h-12 text-cream-50" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V95.8C59.71,118,130.83,121.26,193.59,109.11,238.16,100.56,281.33,76.6,321.39,56.44Z" fill="currentColor"></path>
        </svg>
    </div>
</div>

<!-- QUICK ACCESS PANEL -->
<div class="bg-cream-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Stat 1 -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-cream-200 text-center hover:shadow-md transition-shadow">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-emerald-100 text-emerald-600 mb-6">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-display font-semibold text-navy-900">Penduduk</h3>
                <p class="mt-2 text-3xl font-bold text-teal-600">3,450</p>
                <p class="text-sm text-gray-500 mt-1">Jiwa</p>
            </div>
            
            <!-- Stat 2 -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-cream-200 text-center hover:shadow-md transition-shadow">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-warm-yellow-100 text-warm-yellow-600 mb-6">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <h3 class="text-xl font-display font-semibold text-navy-900">Keluarga</h3>
                <p class="mt-2 text-3xl font-bold text-teal-600">1,200</p>
                <p class="text-sm text-gray-500 mt-1">Kepala Keluarga</p>
            </div>

            <!-- Stat 3 -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-cream-200 text-center hover:shadow-md transition-shadow">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-navy-100 text-navy-600 mb-6">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
                <h3 class="text-xl font-display font-semibold text-navy-900">Luas Wilayah</h3>
                <p class="mt-2 text-3xl font-bold text-teal-600">450</p>
                <p class="text-sm text-gray-500 mt-1">Hektar</p>
            </div>
        </div>
    </div>
</div>

<!-- LATEST NEWS -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-base text-emerald-600 font-semibold tracking-wide uppercase">Informasi</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-navy-900 sm:text-4xl font-display">Kabar Terbaru</p>
            </div>
            <a href="/news" class="hidden sm:inline-flex items-center text-teal-600 hover:text-teal-700 font-medium">
                Lihat Semua Berita <span aria-hidden="true" class="ml-1">&rarr;</span>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @php
                $latestNews = \App\Models\News::where('status', \App\Enums\PageStatus::PUBLISHED->value)
                    ->orderBy('published_at', 'desc')
                    ->take(3)
                    ->get();
            @endphp
            
            @forelse($latestNews as $news)
                <a href="/news/{{ $news->slug }}" class="flex flex-col rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden bg-cream-50 border border-cream-100 group">
                    <div class="flex-shrink-0 h-48 bg-gray-200 overflow-hidden">
                        @if($news->featured_image_id)
                            @php
                                $imgUrl = null;
                                try {
                                    $media = \App\Models\Media::find($news->featured_image_id);
                                    if ($media && $media->invisible_watermark_status === 'verified') {
                                        $deriv = $media->getPublicDerivative('medium');
                                        if ($deriv) $imgUrl = Storage::disk('public')->url($deriv->file_path);
                                    }
                                } catch(\Exception $e){}
                            @endphp
                            @if($imgUrl)
                                <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="{{ $imgUrl }}" alt="{{ $news->title }}">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-teal-100 text-teal-300">
                                    <svg class="h-12 w-12" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71z"/></svg>
                                </div>
                            @endif
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-teal-100 text-teal-300">
                                <svg class="h-12 w-12" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71z"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 p-6 flex flex-col justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-emerald-600">
                                {{ $news->category ? $news->category->name : 'Umum' }}
                            </p>
                            <div class="block mt-2">
                                <p class="text-xl font-semibold text-navy-900 group-hover:text-teal-600 transition-colors font-display">{{ $news->title }}</p>
                                <p class="mt-3 text-base text-gray-500 line-clamp-3">{{ $news->excerpt ?? Str::limit(strip_tags($news->content), 100) }}</p>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center text-sm text-gray-500">
                            <time datetime="{{ $news->published_at->format('Y-m-d') }}">
                                {{ $news->published_at->format('d M Y') }}
                            </time>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-3 text-center py-12 bg-cream-50 rounded-2xl border border-cream-200">
                    <p class="text-gray-500 text-lg">Belum ada berita yang diterbitkan.</p>
                </div>
            @endforelse
        </div>
        
        <div class="mt-8 text-center sm:hidden">
            <a href="/news" class="inline-flex items-center px-4 py-2 border border-teal-600 text-base font-medium rounded-md text-teal-600 bg-transparent hover:bg-teal-50 transition-colors">
                Lihat Semua Berita
            </a>
        </div>
    </div>
</div>
@endsection
