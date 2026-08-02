<x-filament-widgets::widget>
    @php
        $eventLabels = [
            'created' => 'Membuat',
            'updated' => 'Memperbarui',
            'deleted' => 'Menghapus',
            'restored' => 'Memulihkan',
        ];
        $subjectLabels = [
            'News' => 'berita',
            'Page' => 'halaman',
            'Document' => 'dokumen',
            'GalleryAlbum' => 'galeri',
            'Media' => 'media',
            'Location' => 'lokasi',
            'Menu' => 'navigasi',
        ];
        $subjectIcons = [
            'News' => 'heroicon-o-newspaper',
            'Page' => 'heroicon-o-document-text',
            'Document' => 'heroicon-o-folder-open',
            'GalleryAlbum' => 'heroicon-o-photo',
            'Media' => 'heroicon-o-photo',
            'Location' => 'heroicon-o-map-pin',
            'Menu' => 'heroicon-o-bars-3-bottom-left',
        ];
    @endphp

    <section class="admin-dashboard-card admin-activity-card" aria-labelledby="recent-activity-heading">
        <header class="admin-dashboard-card-header admin-activity-header">
            <div>
                <h2 id="recent-activity-heading" class="admin-dashboard-card-title">Aktivitas Terbaru</h2>
                <p class="admin-dashboard-card-description">Perubahan terakhir yang tercatat di CMS.</p>
            </div>
            <a class="admin-card-link" href="{{ \App\Filament\Resources\AuditLogs\AuditLogResource::getUrl('index') }}">
                Lihat Semua
                <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" />
            </a>
        </header>

        <div class="admin-activity-list">
            @forelse ($activities as $activity)
                @php
                    $subjectClass = is_string($activity->subject_type) ? class_basename($activity->subject_type) : null;
                    $event = $eventLabels[$activity->event_type] ?? 'Melakukan perubahan pada';
                    $subject = $subjectLabels[$subjectClass] ?? 'data website';
                    $icon = $subjectIcons[$subjectClass] ?? 'heroicon-o-clock';
                    $values = is_array($activity->new_values) ? $activity->new_values : $activity->old_values;
                    $target = null;

                    if (is_array($values)) {
                        foreach (['title', 'name', 'original_name', 'filename', 'label'] as $key) {
                            if (isset($values[$key]) && is_scalar($values[$key])) {
                                $target = trim((string) $values[$key]);
                                break;
                            }
                        }
                    }

                    $createdAt = null;

                    try {
                        if ($activity->created_at instanceof \DateTimeInterface) {
                            $createdAt = $activity->created_at
                                ->setTimezone(new \DateTimeZone('Asia/Jakarta'))
                                ->format('d/m/Y H.i');
                        }
                    } catch (\Throwable) {
                        $createdAt = null;
                    }
                @endphp
                <article class="admin-activity-item">
                    <div class="admin-activity-icon">
                        <x-filament::icon :icon="$icon" class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="admin-activity-title">
                            {{ $event }} {{ $subject }}
                            @if (filled($target))
                                <strong>{{ $target }}</strong>
                            @endif
                        </p>
                        <p class="admin-activity-meta">
                            {{ $createdAt ?? 'Waktu tidak tersedia' }}
                            <span aria-hidden="true">&middot;</span>
                            oleh {{ $activity->admin?->name ?: ($activity->admin?->username ?: 'Sistem') }}
                        </p>
                    </div>
                    <x-filament::icon icon="heroicon-o-chevron-right" class="admin-activity-chevron size-4" />
                </article>
            @empty
                <div class="admin-activity-empty">
                    <div class="admin-activity-empty-icon">
                        <x-filament::icon icon="heroicon-o-clock" class="size-5" />
                    </div>
                    <p class="font-medium">Belum ada aktivitas terbaru</p>
                    <p class="text-sm">Perubahan yang dilakukan admin akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-filament-widgets::widget>
