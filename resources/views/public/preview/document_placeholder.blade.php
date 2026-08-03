@extends('layouts.public')

@section('title', 'Pratinjau Dokumen')

@section('content')
<div class="py-12 bg-slate-50 min-h-[60vh] flex flex-col items-center justify-center">
    <div class="max-w-2xl w-full mx-auto px-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold text-slate-900 mb-2">{{ $document->title ?? 'Dokumen Tanpa Judul' }}</h1>
            <p class="text-slate-500 mb-6">
                Kategori: <span class="font-medium text-slate-700">{{ $document->category?->name ?? '-' }}</span>
            </p>
            
            <div class="bg-blue-50 border border-blue-100 text-blue-800 text-sm p-4 rounded-lg flex items-start text-left mb-6">
                <svg class="w-5 h-5 flex-shrink-0 mr-3 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p>
                    <strong>Mode Pratinjau:</strong> Berkas dokumen sesungguhnya tidak dapat diunduh pada mode pratinjau. Anda melihat halaman ini untuk memastikan metadata dan status dokumen sudah benar.
                </p>
            </div>
            
            <button disabled class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-slate-400 cursor-not-allowed">
                Unduh Dokumen
            </button>
        </div>
    </div>
</div>
@endsection
