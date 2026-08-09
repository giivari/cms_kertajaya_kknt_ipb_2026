@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
<!-- HERO SECTION -->
<div class="relative bg-navy-900 overflow-hidden">
    @php
        $heroImage = null;
        try {
            // Find the most recently uploaded verified image to use as hero, ideally landscape if we could tell
            $media = \App\Models\Media::where('invisible_watermark_status', 'verified')
                ->where('mime_type', 'like', 'image/%')
                ->latest()
                ->first();
            if ($media) {
                $deriv = $media->getPublicDerivative('large');
                if ($deriv) {
                    $heroImage = Storage::disk('public')->url($deriv->filename);
                }
            }
        } catch (\Exception $e) {}
    @endphp
    
    <div class="absolute inset-0">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="Hero" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-teal-900/90 to-navy-900/80 mix-blend-multiply"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-r from-teal-900 to-navy-900 mix-blend-multiply"></div>
        @endif
    </div>
    
    <div class="relative w-full mx-auto py-24 px-4 sm:py-32 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32 flex flex-col items-center text-center">
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
            <a href="/halaman/profil-desa" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-teal-600 hover:bg-teal-500 transition-colors duration-200 shadow-sm">
                Profil Desa
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

<!-- QUICK ACCESS PANEL / STATISTICS -->
<!-- Hidden until verified village dataset is implemented -->

<!-- LATEST NEWS -->
<div class="py-16 bg-white">
    <div class="w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32">
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
                                    if ($media && $media->invisible_watermark_status\?->value === 'verified') {
                                        $deriv = $media->getPublicDerivative('medium');
                                        if ($deriv) $imgUrl = Storage::disk('public')->url($deriv->filename);
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

<!-- GALLERY MOSAIC -->
<div class="py-16 bg-cream-50">
    <div class="w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-base text-emerald-600 font-semibold tracking-wide uppercase">Dokumentasi</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-navy-900 sm:text-4xl font-display">Galeri Desa</p>
            </div>
            <a href="/galeri" class="hidden sm:inline-flex items-center text-teal-600 hover:text-teal-700 font-medium">
                Lihat Semua Galeri <span aria-hidden="true" class="ml-1">&rarr;</span>
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @forelse($latestAlbums as $index => $album)
                <a href="/galeri/{{ $album->slug }}" class="{{ $index === 0 ? 'md:col-span-2 md:row-span-2' : 'md:col-span-2' }} rounded-xl overflow-hidden relative group h-64 {{ $index === 0 ? 'md:h-full' : '' }}">
                    @php
                        $coverUrl = null;
                        if ($album->cover_image_id) {
                            try {
                                $media = \App\Models\Media::find($album->cover_image_id);
                                if ($media && $media->invisible_watermark_status\?->value === 'verified') {
                                    $deriv = $media->getPublicDerivative($index === 0 ? 'large' : 'medium');
                                    if ($deriv) $coverUrl = Storage::disk('public')->url($deriv->filename);
                                }
                            } catch(\Exception $e){}
                        }
                    @endphp
                    @if($coverUrl)
                        <img src="{{ $coverUrl }}" alt="{{ $album->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full bg-teal-100 flex items-center justify-center text-teal-300">
                            <svg class="h-12 w-12" fill="currentColor" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-900/80 via-navy-900/20 to-transparent opacity-80 group-hover:opacity-100 transition-opacity"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-6">
                        <h3 class="text-white text-xl font-bold font-display truncate">{{ $album->title }}</h3>
                        <p class="text-cream-100 mt-1">{{ $album->published_at->format('d M Y') }}</p>
                    </div>
                </a>
            @empty
                <div class="col-span-4 text-center py-12 bg-white rounded-2xl border border-cream-200">
                    <p class="text-gray-500 text-lg">Belum ada album galeri.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- LATEST DOCUMENTS -->
<div class="py-16 bg-white border-t border-cream-200">
    <div class="w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-base text-emerald-600 font-semibold tracking-wide uppercase">Informasi Publik</h2>
                <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-navy-900 sm:text-4xl font-display">Dokumen Terbaru</p>
            </div>
            <a href="/dokumen" class="hidden sm:inline-flex items-center text-teal-600 hover:text-teal-700 font-medium">
                Lihat Semua Dokumen <span aria-hidden="true" class="ml-1">&rarr;</span>
            </a>
        </div>
        
        <div class="bg-white shadow overflow-hidden sm:rounded-md border border-cream-200">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($latestDocuments as $doc)
                    <li>
                        <a href="/dokumen/{{ $doc->slug }}/download" class="block hover:bg-cream-50 transition-colors">
                            <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-navy-900 truncate">{{ $doc->title }}</p>
                                        <p class="mt-1 flex items-center text-sm text-gray-500">
                                            <span>{{ $doc->category ? $doc->category->name : 'Umum' }}</span>
                                            <span class="mx-2">&bull;</span>
                                            <span>{{ $doc->published_at->format('d M Y') }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center text-teal-600 font-medium">
                                    Unduh
                                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-gray-500">Belum ada dokumen publik yang tersedia.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<!-- FINAL CTA -->
<div class="bg-navy-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-teal-900 to-navy-900 mix-blend-multiply opacity-50"></div>
    <div class="w-full mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-12 xl:px-24 2xl:px-32 lg:flex lg:items-center lg:justify-between relative z-10">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl font-display">
            <span class="block text-lime-400">Punya pertanyaan atau masukan?</span>
            <span class="block">Hubungi pemerintah desa sekarang.</span>
        </h2>
        <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-md shadow">
                <a href="/halaman/profil-desa" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-navy-900 bg-lime-400 hover:bg-lime-300 transition-colors">
                    Pelajari Lebih Lanjut
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
