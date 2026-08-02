@php
    use App\Filament\Support\MediaThumbnail;
    use App\Models\Media;
    use Illuminate\Support\Facades\Storage;

    $mediaId = filter_var($get($mediaStatePath), FILTER_VALIDATE_INT);
    $media = $mediaId
        ? Media::query()->with('derivatives')->find($mediaId)
        : null;
    $path = MediaThumbnail::path($media);
    $url = $path
        ? Storage::disk(MediaThumbnail::disk($media))->url($path)
        : MediaThumbnail::placeholderUrl($media?->mime_type);
@endphp

<div class="admin-gallery-item-preview" role="group" aria-label="Pratinjau media terpilih">
    <img
        src="{{ $url }}"
        alt="{{ $media ? 'Pratinjau '.$media->original_filename : 'Belum ada gambar dipilih' }}"
        class="admin-gallery-item-preview-image"
    >
    <div class="admin-gallery-item-preview-copy">
        <span class="admin-gallery-item-preview-name">
            {{ $media?->original_filename ?? 'Belum ada gambar dipilih' }}
        </span>
        <span class="admin-gallery-item-preview-status">
            {{ $path ? 'Siap digunakan' : 'Pilih gambar yang sudah selesai diproses dan terverifikasi' }}
        </span>
    </div>
</div>
