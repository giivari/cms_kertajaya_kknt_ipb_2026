@php
    $value = fn (string $key, mixed $default = null) => data_get($state, $key, $default);
    $statusLabels = ['draft' => 'Draf', 'published' => 'Terbit', 'archived' => 'Diarsipkan'];
    $media = function ($id) {
        if (! $id || is_object($id)) {
            return null;
        }

        return \App\Models\Media::find($id);
    };
    $mediaUrl = function ($candidate) use ($media) {
        if ($candidate instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            try {
                return $candidate->temporaryUrl();
            } catch (\Throwable) {
                return null;
            }
        }

        return $media($candidate)?->url;
    };
    $categoryName = function (string $model, $id) {
        return $id ? $model::find($id)?->name : null;
    };
@endphp

<div class="max-h-[75vh] overflow-y-auto overflow-x-hidden rounded-xl bg-gray-50 p-3 sm:p-6">
    @if($type === 'news')
        @php
            $image = $mediaUrl($value('featured_media_id'));
            $category = $categoryName(\App\Models\NewsCategory::class, $value('news_category_id'));
        @endphp
        <div class="mx-auto max-w-4xl space-y-8">
            <section class="overflow-hidden rounded-2xl bg-white shadow">
                @if($image)<img src="{{ $image }}" alt="" class="h-56 w-full object-cover">@endif
                <div class="p-6">
                    <div class="mb-2 text-sm font-semibold text-emerald-700">{{ $category ?: 'Tanpa kategori' }}</div>
                    <h2 class="text-3xl font-bold text-gray-900">{{ $value('title', 'Judul berita') }}</h2>
                    <p class="mt-3 text-gray-600">{{ $value('excerpt', 'Ringkasan berita akan tampil di sini.') }}</p>
                    <span class="mt-4 inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm">{{ $statusLabels[$value('status')] ?? 'Draf' }}</span>
                </div>
            </section>
            <article class="rounded-2xl bg-white p-6 shadow">
                <p class="mb-3 text-sm font-semibold text-emerald-700">{{ $category ?: 'Tanpa kategori' }}</p>
                <h1 class="text-4xl font-bold">{{ $value('title', 'Judul berita') }}</h1>
                @if($image)<img src="{{ $image }}" alt="" class="my-6 max-h-[28rem] w-full rounded-xl object-cover">@endif
                <div class="prose max-w-none">{!! $value('content', 'Isi berita akan tampil di sini.') !!}</div>
            </article>
        </div>
    @elseif($type === 'page')
        @php $pageImage = $mediaUrl($value('featured_media_id')); @endphp
        <div class="overflow-hidden rounded-2xl bg-white shadow">
            <header class="relative bg-emerald-700 px-5 py-12 text-center text-white">
                @if($pageImage)<img src="{{ $pageImage }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30">@endif
                <div class="relative">
                    <h1 class="text-4xl font-bold">{{ $value('title', 'Judul halaman') }}</h1>
                    <p class="mx-auto mt-3 max-w-2xl">{{ $value('excerpt') }}</p>
                </div>
            </header>
            <div class="space-y-10 p-5 sm:p-8">
                @forelse($value('builder_sections', []) as $section)
                    @if(data_get($section, 'is_visible', true))
                        @php
                            $layout = data_get($section, 'layout_type', 'single_column');
                            $grid = match ($layout) {
                                'two_columns' => 'grid grid-cols-1 md:grid-cols-2 gap-6',
                                'three_columns' => 'grid grid-cols-1 md:grid-cols-3 gap-6',
                                default => 'flex flex-col gap-6',
                            };
                        @endphp
                        <section class="{{ $layout === 'hero' ? 'rounded-2xl bg-emerald-50 p-6' : '' }}">
                            @if(data_get($section, 'name') && ! in_array($layout, ['hero', 'full_width'], true))
                                <h2 class="mb-5 text-2xl font-bold">{{ data_get($section, 'name') }}</h2>
                            @endif
                            <div class="{{ $grid }}">
                                @foreach(data_get($section, 'components', []) as $component)
                                    @php
                                        $componentType = data_get($component, 'type');
                                        $data = data_get($component, 'data', []);
                                    @endphp
                                    @if(data_get($data, 'is_visible', true) && $componentType)
                                        <div class="min-w-0">
                                            @includeIf('pages.components.'.$componentType, ['data' => $data])
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endif
                @empty
                    <p class="py-10 text-center text-gray-500">Tambahkan bagian untuk melihat isi halaman.</p>
                @endforelse
            </div>
        </div>
    @elseif($type === 'location')
        @php
            $lat = $value('latitude');
            $lng = $value('longitude');
            $validCoordinates = $lat !== null && $lng !== null;
            $locationImage = $mediaUrl($value('media_id'));
        @endphp
        <article class="mx-auto max-w-4xl overflow-hidden rounded-2xl bg-white shadow">
            @if($locationImage)<img src="{{ $locationImage }}" alt="" class="h-64 w-full object-cover">@endif
            <div class="p-6">
                <p class="font-semibold text-emerald-700">{{ $categoryName(\App\Models\LocationCategory::class, $value('location_category_id')) ?: 'Kategori lokasi' }}</p>
                <h1 class="mt-2 text-3xl font-bold">{{ $value('name', 'Nama lokasi') }}</h1>
                <p class="mt-3 text-gray-600">{{ $value('address') }}</p>
                <p class="mt-4">{{ $value('short_description') }}</p>
                @if($validCoordinates)
                    @php
                        $delta = 0.01;
                        $mapUrl = 'https://www.openstreetmap.org/export/embed.html?bbox='.
                            (($lng - $delta).','.($lat - $delta).','.($lng + $delta).','.($lat + $delta)).
                            '&layer=mapnik&marker='.$lat.','.$lng;
                    @endphp
                    <iframe src="{{ $mapUrl }}" class="mt-6 h-72 w-full rounded-xl border bg-gray-100" loading="lazy"
                            title="Pratinjau peta {{ $value('name', 'lokasi') }}"></iframe>
                @else
                    <p class="mt-6 rounded-xl bg-amber-50 p-4 text-amber-800">Lengkapi garis lintang dan garis bujur untuk melihat peta.</p>
                @endif
            </div>
        </article>
    @elseif($type === 'gallery')
        @php $cover = $mediaUrl($value('cover_media_id')); @endphp
        <div class="mx-auto max-w-6xl">
            @if($cover)
                <img src="{{ $cover }}" alt="" class="mb-6 h-64 w-full rounded-2xl object-cover shadow">
            @else
                <div class="mb-6 flex h-40 items-center justify-center rounded-2xl bg-gray-200 text-gray-500">Belum ada gambar sampul</div>
            @endif
            <h1 class="text-3xl font-bold">{{ $value('title', 'Judul galeri') }}</h1>
            <p class="mt-2 text-gray-600">{{ $value('description') }}</p>
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @forelse($value('items', []) as $item)
                    @php $image = $mediaUrl(data_get($item, 'media_id')); @endphp
                    <figure class="overflow-hidden rounded-xl bg-white shadow">
                        @if($image)<img src="{{ $image }}" alt="{{ data_get($item, 'alt_text', '') }}" class="h-44 w-full object-cover">@endif
                        <figcaption class="p-3 text-sm">{{ data_get($item, 'caption', 'Tanpa keterangan') }}</figcaption>
                    </figure>
                @empty
                    <p class="col-span-full rounded-xl bg-white p-6 text-center text-gray-500">Belum ada foto yang dipilih.</p>
                @endforelse
            </div>
        </div>
    @elseif($type === 'document')
        @php
            $file = $media($value('file_media_id'));
            $thumbnail = $mediaUrl($value('thumbnail_media_id'));
        @endphp
        <article class="mx-auto max-w-3xl overflow-hidden rounded-2xl bg-white shadow">
            @if($thumbnail)<img src="{{ $thumbnail }}" alt="" class="h-56 w-full object-cover">@endif
            <div class="p-6">
                <p class="font-semibold text-blue-700">{{ $categoryName(\App\Models\DocumentCategory::class, $value('document_category_id')) ?: 'Tanpa kategori' }}</p>
                <h1 class="mt-2 text-3xl font-bold">{{ $value('title', 'Judul dokumen') }}</h1>
                <p class="mt-3 text-gray-600">{{ $value('description') }}</p>
                <div class="mt-5 rounded-xl bg-gray-50 p-4">
                    <strong>{{ $file?->original_filename ?: 'Pilih berkas dokumen' }}</strong>
                    <p class="text-sm text-gray-600">{{ $file?->mime_type ?: 'Jenis berkas belum tersedia' }} · {{ $file ? \Illuminate\Support\Number::fileSize($file->size) : 'Ukuran belum tersedia' }}</p>
                    @if($file?->mime_type === 'application/pdf')
                        <p class="mt-3 text-sm text-gray-500">PDF dapat dibuka setelah dokumen disimpan dan lolos pemrosesan media.</p>
                    @endif
                </div>
            </div>
        </article>
    @elseif($type === 'media')
        @php
            $upload = collect(\Illuminate\Support\Arr::wrap($value('file')))->first();
            $uploadUrl = $mediaUrl($upload);
            $mime = $upload instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ? $upload->getMimeType() : null;
            $size = $upload instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ? $upload->getSize() : null;
        @endphp
        <div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow">
            <h1 class="text-2xl font-bold">{{ $value('original_filename', 'Berkas yang dipilih') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $mime ?: 'Jenis belum tersedia' }} @if($size) · {{ \Illuminate\Support\Number::fileSize($size) }} @endif</p>
            @if($uploadUrl && str_starts_with((string) $mime, 'image/'))
                <img src="{{ $uploadUrl }}" alt="" class="mt-5 max-h-[32rem] w-full object-contain">
            @elseif($uploadUrl && $mime === 'application/pdf')
                <iframe src="{{ $uploadUrl }}" class="mt-5 h-[32rem] w-full rounded border" title="Pratinjau PDF"></iframe>
            @else
                <div class="mt-5 rounded-xl bg-gray-50 p-8 text-center text-gray-500">Pratinjau visual tidak tersedia untuk format ini.</div>
            @endif
        </div>
    @elseif($type === 'menu')
        @php
            $visibleItems = collect($value('items', []))->filter(fn ($item) => data_get($item, 'is_visible', true));
            $isFooter = $value('location') === \App\Models\Menu::FOOTER;
            $locationLabel = \App\Models\Menu::supportedLocations()[$value('location')] ?? 'Lokasi Menu';
        @endphp
        <h1 class="mb-4 text-xl font-bold text-gray-900">{{ $locationLabel }}</h1>
        @if($isFooter)
            <div class="rounded-2xl bg-gray-900 p-6 text-white shadow">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-200">Tautan Cepat</h2>
                <nav class="grid gap-2">
                    @forelse($visibleItems as $item)
                        <span class="text-gray-300">{{ data_get($item, 'label', 'Nama yang Tampil') }}</span>
                    @empty
                        <span class="text-gray-400">Belum ada tautan yang ditampilkan.</span>
                    @endforelse
                </nav>
            </div>
        @else
            <div class="space-y-6">
                <section class="rounded-2xl bg-white p-5 shadow">
                    <p class="mb-4 text-xs font-semibold uppercase text-gray-500">Desktop</p>
                    <nav class="flex flex-wrap items-start gap-5">
                        @forelse($visibleItems as $item)
                            <div>
                                <span class="font-medium text-gray-900">{{ data_get($item, 'label', 'Nama yang Tampil') }}</span>
                                @php $children = collect(data_get($item, 'children', []))->filter(fn ($child) => data_get($child, 'is_visible', true)); @endphp
                                @if($children->isNotEmpty())
                                    <div class="mt-2 space-y-1 rounded-lg bg-gray-100 p-3 text-gray-800">
                                        @foreach($children as $child)
                                            <div>{{ data_get($child, 'label', 'Nama yang Tampil') }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <span class="text-gray-500">Belum ada tautan yang ditampilkan.</span>
                        @endforelse
                    </nav>
                </section>
                <section class="mx-auto max-w-sm rounded-2xl bg-white p-5 shadow">
                    <p class="mb-4 text-xs font-semibold uppercase text-gray-500">Mobile</p>
                    <nav class="grid gap-2">
                        @forelse($visibleItems as $item)
                            <div class="rounded-lg bg-gray-50 px-3 py-2 text-gray-900">
                                {{ data_get($item, 'label', 'Nama yang Tampil') }}
                            </div>
                        @empty
                            <span class="text-gray-500">Belum ada tautan yang ditampilkan.</span>
                        @endforelse
                    </nav>
                </section>
            </div>
        @endif
    @elseif(in_array($type, ['location-category', 'news-category', 'document-category'], true))
        <div class="mx-auto max-w-lg rounded-2xl bg-white p-6 shadow">
            <h2 class="text-2xl font-bold">{{ $value('name', 'Nama kategori') }}</h2>
            <p class="mt-2 text-gray-600">{{ $value('description', 'Deskripsi kategori') }}</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800">{{ $value('name', 'Kategori') }}</span>
                @if($type === 'location-category')<span class="rounded bg-gray-100 px-3 py-1 text-sm">Filter Peta</span>@endif
                @if($type === 'document-category')<span class="rounded bg-blue-100 px-3 py-1 text-sm">Filter Dokumen</span>@endif
                @if($type === 'location-category')<span class="text-sm">{{ $value('is_active', true) ? 'Aktif' : 'Tidak Aktif' }}</span>@endif
            </div>
        </div>
    @elseif($type === 'settings')
        <div class="overflow-hidden rounded-2xl bg-white shadow">
            <header class="flex items-center gap-4 bg-emerald-700 p-6 text-white">
                @if($mediaUrl($value('village_logo')))<img src="{{ $mediaUrl($value('village_logo')) }}" alt="" class="h-14 w-14 object-contain">@endif
                <div><h1 class="text-3xl font-bold">{{ $value('village_name', 'Nama Desa') }}</h1><p>{{ $value('village_description') }}</p></div>
            </header>
            <div class="grid gap-6 p-6 md:grid-cols-2">
                <section><h2 class="font-bold">Mesin Pencari</h2><p>{{ $value('meta_title') }}</p><p class="text-gray-600">{{ $value('meta_description') }}</p></section>
                <section><h2 class="font-bold">Kontak</h2><p>{{ $value('contact_email') }}</p><p>{{ $value('contact_phone') }}</p><p>{{ $value('address_street') }}</p></section>
                <section><h2 class="font-bold">Media Sosial</h2><p>{{ $value('social_facebook') }}</p><p>{{ $value('social_instagram') }}</p><p>{{ $value('social_youtube') }}</p></section>
                <section><h2 class="font-bold">Tanda Air</h2><p>{{ $value('enable_visible_watermark') ? $value('watermark_text', 'Aktif') : 'Tidak aktif' }}</p></section>
            </div>
            <footer class="bg-gray-900 p-5 text-white">{{ $value('footer_copyright_text', 'Teks kaki halaman') }}</footer>
        </div>
    @endif
</div>
