@extends('layouts.public')

@section('title', 'True Frontend Preview')
@section('meta_description', 'Preview renderer aktif.')

@section('content')
<div class="container mx-auto px-4 py-16 flex items-center justify-center min-h-[50vh]">
    <div class="text-center max-w-2xl bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 text-primary mb-6">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-4">True Frontend Preview Aktif</h1>
        <p class="text-gray-600 mb-6">
            Infrastruktur <em>rendering</em> publik untuk pratinjau berhasil dimuat. Konten dengan tipe <strong class="text-gray-900 bg-gray-100 px-2 py-1 rounded">{{ $type }}</strong> akan didukung penuh pada fase berikutnya.
        </p>
        <div class="text-sm text-gray-500 flex flex-col gap-2 items-center">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Phase 2
            </span>
            <p>Mode ini hanya mensimulasikan pemuatan aset frontend (layout, CSS, dan JavaScript publik).</p>
        </div>
    </div>
</div>
@endsection
