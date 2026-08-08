@extends('layouts.public')

@section('title', 'Peta Desa')
@section('seo_description', 'Direktori lokasi dan fasilitas Desa Kertajaya.')

@section('content')
<div class="min-h-screen bg-warm-white py-12 lg:py-20">
    <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
        <div class="mb-8 max-w-3xl">
            <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-teal">Direktori Desa</p>
            <h1 class="font-display text-3xl font-bold text-navy sm:text-4xl">Peta Desa Kertajaya</h1>
            <p class="mt-3 text-gray-600">Temukan kantor pelayanan, fasilitas umum, dan lokasi penting di Desa Kertajaya.</p>
        </div>

        <nav class="mb-6 flex gap-2 overflow-x-auto pb-2" aria-label="Filter kategori lokasi">
            <a href="{{ route('public.map.index') }}"
               class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors {{ $selectedCategory === '' ? 'bg-teal text-white' : 'border border-border bg-white text-navy hover:border-teal' }}">
                Semua
            </a>
            @foreach($categories as $category)
                <a href="{{ route('public.map.index', ['category' => $category->slug]) }}"
                   class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors {{ $selectedCategory === $category->slug ? 'bg-teal text-white' : 'border border-border bg-white text-navy hover:border-teal' }}">
                    {{ $category->name }}
                </a>
            @endforeach
        </nav>

        @if($locations->isEmpty())
            <div class="rounded-3xl border border-border bg-white p-10 text-center">
                <svg class="mx-auto mb-4 h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h2 class="font-display text-lg font-semibold text-navy">Belum Ada Lokasi</h2>
                <p class="mt-2 text-sm text-gray-500">Lokasi untuk kategori ini belum tersedia.</p>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.8fr)]">
                <div id="locations-map" class="h-[420px] overflow-hidden rounded-3xl border border-border bg-gray-100 shadow-sm lg:sticky lg:top-24 lg:h-[620px]" aria-label="Peta lokasi Desa Kertajaya"></div>

                <div class="space-y-3 lg:max-h-[620px] lg:overflow-y-auto lg:pr-2" id="location-list">
                    @foreach($locations as $location)
                        <article
                            class="js-location-item cursor-pointer rounded-2xl border border-border bg-white p-5 transition-all hover:border-teal/40 hover:shadow-md"
                            tabindex="0"
                            data-location-id="{{ $location->id }}"
                            data-latitude="{{ $location->latitude }}"
                            data-longitude="{{ $location->longitude }}"
                            data-name="{{ $location->name }}"
                            data-category="{{ $location->category->name }}"
                            data-summary="{{ $location->address ?: $location->short_description }}"
                            data-image="{{ $location->media?->url }}"
                            data-url="{{ route('public.map.show', $location) }}"
                        >
                            <div class="flex items-start gap-4">
                                @if($location->media)
                                    <img src="{{ $location->media->url }}" alt="" class="h-20 w-20 shrink-0 rounded-xl object-cover" loading="lazy">
                                @else
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cream text-teal">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-teal">{{ $location->category->name }}</p>
                                    <h2 class="mt-1 font-display text-lg font-semibold text-navy">{{ $location->name }}</h2>
                                    @if($location->address || $location->short_description)
                                        <p class="mt-2 line-clamp-2 text-sm text-gray-500">{{ $location->address ?: $location->short_description }}</p>
                                    @endif
                                    <a href="{{ route('public.map.show', $location) }}" class="mt-3 inline-flex text-sm font-medium text-teal hover:text-emerald">
                                        Lihat detail <span aria-hidden="true">&nbsp;&rarr;</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
