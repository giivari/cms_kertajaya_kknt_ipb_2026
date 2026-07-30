@extends('layouts.public')

@section('title', 'Hasil Pencarian')

@section('content')
<div class="bg-warm-white min-h-screen py-12 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Search Header --}}
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-display font-bold text-navy mb-4">Hasil Pencarian</h1>
            <form action="{{ route('public.search') }}" method="GET" class="flex flex-col gap-3 sm:flex-row" id="search-form">
                <div class="relative w-full sm:flex-1">
                    <svg class="pointer-events-none absolute left-4 top-1/2 z-10 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        name="q"
                        value="{{ e($query) }}"
                        placeholder="Cari halaman, berita, atau dokumen…"
                        class="w-full rounded-2xl border border-border bg-white py-3 pl-12 pr-5 text-base text-navy placeholder-gray-400 transition-all duration-200 focus:border-teal focus:outline-none focus:ring-2 focus:ring-teal/40"
                        minlength="2"
                        maxlength="100"
                        id="search-input"
                        autocomplete="off"
                    >
                </div>
                <button type="submit" class="w-full rounded-2xl bg-teal px-6 py-3 text-base font-medium text-white transition-colors duration-200 hover:bg-emerald sm:w-auto" id="search-submit">
                    Cari
                </button>
            </form>
        </div>

        @if(mb_strlen($query) >= 2 && mb_strlen($query) <= 100)
            {{-- Results Summary --}}
            <p class="text-gray-500 text-sm mb-8" id="search-summary">
                @if($totalCount > 0)
                    Ditemukan <span class="font-semibold text-navy">{{ $totalCount }}</span> hasil untuk
                    "<span class="font-semibold text-teal">{{ e($query) }}</span>"
                @else
                    Tidak ada hasil untuk "<span class="font-semibold text-teal">{{ e($query) }}</span>"
                @endif
            </p>

            @if($totalCount === 0)
                {{-- Empty State --}}
                <div class="bg-white rounded-3xl border border-border p-10 text-center" id="empty-state">
                    <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-navy mb-2">Tidak Ditemukan</h3>
                    <p class="text-gray-500 text-sm">Coba gunakan kata kunci yang berbeda atau lebih umum.</p>
                </div>
            @endif

            {{-- Pages --}}
            @if($pages->isNotEmpty())
                <section class="mb-10" id="search-results-pages">
                    <h2 class="text-lg font-display font-semibold text-navy mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Halaman
                        <span class="text-sm font-normal text-gray-400">({{ $pages->count() }})</span>
                    </h2>
                    <div class="space-y-3">
                        @foreach($pages as $page)
                            <a href="{{ route('pages.show', $page->slug) }}" class="block bg-white rounded-2xl border border-border p-5 hover:border-teal/40 hover:shadow-md transition-all duration-200 group">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-navy group-hover:text-teal transition-colors duration-200 truncate">{{ $page->title }}</h3>
                                        @if($page->excerpt)
                                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($page->excerpt), 150) }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-xs font-medium bg-cream text-navy px-2.5 py-1 rounded-lg">Halaman</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- News --}}
            @if($news->isNotEmpty())
                <section class="mb-10" id="search-results-news">
                    <h2 class="text-lg font-display font-semibold text-navy mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                        Berita
                        <span class="text-sm font-normal text-gray-400">({{ $news->count() }})</span>
                    </h2>
                    <div class="space-y-3">
                        @foreach($news as $article)
                            <a href="{{ route('news.show', $article->slug) }}" class="block bg-white rounded-2xl border border-border p-5 hover:border-teal/40 hover:shadow-md transition-all duration-200 group">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-navy group-hover:text-teal transition-colors duration-200 truncate">{{ $article->title }}</h3>
                                        @if($article->excerpt)
                                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($article->excerpt), 150) }}</p>
                                        @endif
                                        @if($article->published_at)
                                            <p class="text-xs text-gray-400 mt-2">{{ $article->published_at->translatedFormat('d F Y') }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-xs font-medium bg-lime/30 text-navy px-2.5 py-1 rounded-lg">Berita</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Documents --}}
            @if($documents->isNotEmpty())
                <section class="mb-10" id="search-results-documents">
                    <h2 class="text-lg font-display font-semibold text-navy mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Dokumen
                        <span class="text-sm font-normal text-gray-400">({{ $documents->count() }})</span>
                    </h2>
                    <div class="space-y-3">
                        @foreach($documents as $doc)
                            <a href="{{ route('documents.download', $doc->slug) }}" class="block bg-white rounded-2xl border border-border p-5 hover:border-teal/40 hover:shadow-md transition-all duration-200 group">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base font-semibold text-navy group-hover:text-teal transition-colors duration-200 truncate">{{ $doc->title }}</h3>
                                        @if($doc->description)
                                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($doc->description), 150) }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-xs font-medium bg-yellow/30 text-navy px-2.5 py-1 rounded-lg">Dokumen</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @elseif(mb_strlen($query) === 0)
            <p class="text-gray-500 text-sm mb-8">
                Masukkan minimal 2 karakter untuk mencari halaman, berita, atau dokumen.
            </p>
        @elseif(mb_strlen($query) < 2)
            <p class="text-gray-500 text-sm mb-8">Kata kunci minimal 2 karakter.</p>
        @else
            <p class="text-gray-500 text-sm mb-8">Kata kunci maksimal 100 karakter.</p>
        @endif
    </div>
</div>
@endsection
