<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold tracking-tight text-gray-950 dark:text-white">Aksi Cepat</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\News\NewsResource::getUrl('create') }}" icon="heroicon-o-plus-circle" color="primary">
                Buat Berita
            </x-filament::button>
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\Pages\PageResource::getUrl('create') }}" icon="heroicon-o-document-plus" color="primary">
                Buat Halaman
            </x-filament::button>
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\Media\MediaResource::getUrl('create') }}" icon="heroicon-o-arrow-up-tray" color="primary">
                Unggah Media
            </x-filament::button>
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\GalleryAlbums\GalleryAlbumResource::getUrl('create') }}" icon="heroicon-o-photo" color="primary">
                Buat Album
            </x-filament::button>
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\Documents\DocumentResource::getUrl('create') }}" icon="heroicon-o-document-duplicate" color="primary">
                Tambah Dokumen
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
