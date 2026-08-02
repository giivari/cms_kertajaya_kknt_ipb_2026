@php
    use App\Filament\Support\MediaThumbnail;
    use Illuminate\Support\Facades\Storage;

    $record = $getRecord();
    $thumbnailPath = MediaThumbnail::path($record->coverMedia);
    $thumbnailUrl = $thumbnailPath
        ? Storage::disk(MediaThumbnail::disk($record->coverMedia))->url($thumbnailPath)
        : MediaThumbnail::placeholderUrl();
@endphp

<div class="admin-gallery-mobile-album">
    <img
        src="{{ $thumbnailUrl }}"
        alt="Sampul album {{ $record->title }}"
        class="admin-gallery-mobile-album-image"
    />

    <div class="admin-gallery-mobile-album-copy">
        <span class="admin-gallery-mobile-album-title">{{ $record->title }}</span>
        <span class="admin-gallery-mobile-album-count">{{ $record->items_count }} foto</span>
    </div>
</div>
