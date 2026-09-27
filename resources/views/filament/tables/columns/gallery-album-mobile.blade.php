@php
    use App\Filament\Support\MediaThumbnail;

    $record = $getRecord();
    $thumbnailUrl = MediaThumbnail::url($record->coverMedia) ?? MediaThumbnail::placeholderUrl();
@endphp

<div class="admin-gallery-mobile-album">
    <img
        src="{{ $thumbnailUrl }}"
        alt="Sampul album {{ $record->title }}"
        class="admin-gallery-mobile-album-image"
    />

    <div class="admin-gallery-mobile-album-copy">
        <span class="admin-gallery-mobile-album-title">{{ \Illuminate\Support\Str::limit($record->title, 40) }}</span>
        <span class="admin-gallery-mobile-album-count">{{ $record->items_count }} foto</span>
    </div>
</div>
