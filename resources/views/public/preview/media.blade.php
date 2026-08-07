@extends('layouts.public')

@section('title', 'Pratinjau Media')

@section('content')
<div class="container mx-auto px-4 py-4 max-w-4xl">
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 bg-gray-50 border-b border-gray-100 text-center">
            <h1 class="text-2xl font-bold text-gray-800">{{ $state['original_filename'] ?? 'Pratinjau Media' }}</h1>
            @if(!empty($state['file_mime_type']))
                <p class="text-gray-500 mt-2 text-sm">{{ $state['file_mime_type'] }}</p>
            @endif
        </div>
        
        <div class="p-8 flex justify-center items-center bg-gray-100 min-h-[400px]">
            @if(!empty($state['file_url']))
                @if(str_starts_with($state['file_mime_type'] ?? '', 'image/'))
                    <img src="{{ $state['file_url'] }}" alt="{{ $state['alt_text'] ?? '' }}" class="max-w-full h-auto rounded shadow-sm max-h-[70vh] object-contain">
                @else
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-4 text-gray-600">Media siap diunggah. Pratinjau langsung untuk tipe berkas ini tidak tersedia.</p>
                        <a href="{{ $state['file_url'] }}" target="_blank" class="mt-4 inline-block text-blue-600 hover:underline">Buka Berkas</a>
                    </div>
                @endif
            @else
                <div class="text-center text-gray-500">
                    <p>Berkas belum dipilih atau pratinjau tidak tersedia.</p>
                </div>
            @endif
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Teks Alternatif (Alt Text)</h3>
                    <p class="text-gray-800 bg-gray-50 p-3 rounded border border-gray-100">{{ $state['alt_text'] ?: '-' }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Keterangan (Caption)</h3>
                    <p class="text-gray-800 bg-gray-50 p-3 rounded border border-gray-100">{{ $state['caption'] ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
