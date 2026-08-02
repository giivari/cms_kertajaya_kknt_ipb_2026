<x-filament-widgets::widget>
    <section class="admin-dashboard-card admin-quick-actions-card" aria-labelledby="quick-actions-heading">
        <header class="admin-dashboard-card-header">
            <div>
                <h2 id="quick-actions-heading" class="admin-dashboard-card-title">Aksi Cepat</h2>
                <p class="admin-dashboard-card-description">Mulai pekerjaan utama tanpa mencari menu.</p>
            </div>
        </header>

        <div class="admin-quick-actions-grid">
            <a class="admin-quick-action" href="{{ \App\Filament\Resources\News\NewsResource::getUrl('create') }}">
                <span class="admin-quick-action-icon"><x-filament::icon icon="heroicon-o-newspaper" class="size-5" /></span>
                <span>Buat Berita</span>
            </a>
            <a class="admin-quick-action" href="{{ \App\Filament\Resources\Pages\PageResource::getUrl('create') }}">
                <span class="admin-quick-action-icon"><x-filament::icon icon="heroicon-o-document-plus" class="size-5" /></span>
                <span>Buat Halaman</span>
            </a>
            <a class="admin-quick-action" href="{{ \App\Filament\Resources\Media\MediaResource::getUrl('create') }}">
                <span class="admin-quick-action-icon"><x-filament::icon icon="heroicon-o-arrow-up-tray" class="size-5" /></span>
                <span>Unggah Media</span>
            </a>
            <a class="admin-quick-action" href="{{ \App\Filament\Resources\Documents\DocumentResource::getUrl('create') }}">
                <span class="admin-quick-action-icon"><x-filament::icon icon="heroicon-o-folder-plus" class="size-5" /></span>
                <span>Tambah Dokumen</span>
            </a>
        </div>
    </section>
</x-filament-widgets::widget>
