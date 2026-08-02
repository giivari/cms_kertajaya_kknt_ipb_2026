@extends('layouts.public')

@section('title', $location->name)
@section('seo_description', \Illuminate\Support\Str::limit($location->short_description ?: strip_tags($location->description), 150))

@section('content')
<div class="min-h-screen bg-warm-white py-12 lg:py-20">
    <article class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <a href="{{ route('public.map.index') }}" class="mb-6 inline-flex text-sm font-medium text-teal hover:text-emerald">&larr;&nbsp; Kembali ke peta</a>

        <div class="overflow-hidden rounded-3xl border border-border bg-white shadow-sm">
            @if($location->media)
                <img src="{{ $location->media->url }}" alt="{{ $location->name }}" class="h-64 w-full object-cover sm:h-80" loading="lazy">
            @endif

            <div class="p-6 sm:p-10">
                <p class="text-sm font-semibold uppercase tracking-wide text-teal">{{ $location->category->name }}</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-navy sm:text-4xl">{{ $location->name }}</h1>

                @if($location->address)
                    <p class="mt-4 flex items-start gap-2 text-gray-600">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $location->address }}</span>
                    </p>
                @endif

                @if($location->short_description)
                    <p class="mt-6 text-lg leading-relaxed text-gray-600">{{ $location->short_description }}</p>
                @endif

                @if($location->description)
                    <div class="prose prose-slate mt-8 max-w-none">{!! clean($location->description) !!}</div>
                @endif

                <div id="locations-map" class="mt-8 h-[360px] overflow-hidden rounded-2xl border border-border bg-gray-100" aria-label="Peta {{ $location->name }}"></div>
                <div
                    class="js-location-item hidden"
                    data-location-id="{{ $location->id }}"
                    data-latitude="{{ $location->latitude }}"
                    data-longitude="{{ $location->longitude }}"
                    data-name="{{ $location->name }}"
                    data-category="{{ $location->category->name }}"
                    data-summary="{{ $location->address ?: $location->short_description }}"
                    data-image="{{ $location->media?->url }}"
                    data-url="{{ route('public.map.show', $location) }}"
                ></div>
            </div>
        </div>
    </article>
</div>
@endsection
