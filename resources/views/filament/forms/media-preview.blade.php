@php
    $record = $getRecord();
    $path = $record ? \App\Filament\Support\MediaThumbnail::path($record) : null;
    $status = $record ? (is_object($record->processing_status) ? $record->processing_status->value : $record->processing_status) : 'pending';
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="mt-2">
        @if ($path)
            @php
                $url = \Illuminate\Support\Facades\Storage::disk(\App\Filament\Support\MediaThumbnail::disk($record))->url($path);
                $isPdf = $record->mime_type === 'application/pdf';
            @endphp
            @if($isPdf)
                <iframe src="{{ $url }}" class="w-full rounded-lg shadow-sm" style="height: 500px; border: none;"></iframe>
            @else
                <img src="{{ $url }}" alt="Pratinjau" class="rounded-lg shadow-sm" style="max-width: 100%; max-height: 400px; height: auto;">
            @endif
        @elseif ($status === 'pending')
            <div class="p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200 text-sm">
                Media masih dalam status <strong>Menunggu</strong> antrean pemrosesan. Silakan kembali ke tabel dan klik opsi <strong>Proses Ulang</strong>.
            </div>
        @elseif ($status === 'failed')
            <div class="p-4 bg-red-50 text-red-800 rounded-lg border border-red-200 text-sm">
                Pemrosesan media <strong>Gagal</strong>. Silakan kembali ke tabel dan klik opsi <strong>Proses Ulang</strong>.
            </div>
        @else
            <div class="p-4 bg-gray-50 text-gray-500 rounded-lg border border-gray-200 text-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> 
                Pratinjau gambar tidak tersedia.
            </div>
        @endif
    </div>
</x-dynamic-component>
