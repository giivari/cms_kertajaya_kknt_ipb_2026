@extends('layouts.public')

@section('title', 'Pratinjau Kategori')

@section('content')
@php
    $label = match ($type) {
        'news-category' => 'Kategori Berita',
        'document-category' => 'Kategori Dokumen',
        'location-category' => 'Kategori Lokasi',
        default => 'Kategori',
    };
@endphp
<div class="container mx-auto max-w-4xl px-4 py-10">
    <div class="rounded-xl border border-gray-100 bg-white p-8 shadow-sm">
        <p class="mb-2 text-sm font-semibold text-emerald-700">{{ $label }}</p>
        <h1 class="text-3xl font-bold text-gray-900">{{ $state['name'] ?? 'Nama kategori' }}</h1>
        @if(!empty($state['description']))
            <p class="mt-4 text-gray-600">{{ $state['description'] }}</p>
        @endif
        @if($type === 'location-category')
            <p class="mt-5 text-sm text-gray-500">{{ ($state['is_active'] ?? true) ? 'Aktif dalam filter lokasi' : 'Tidak aktif dalam filter lokasi' }}</p>
        @endif
    </div>
</div>
@endsection
