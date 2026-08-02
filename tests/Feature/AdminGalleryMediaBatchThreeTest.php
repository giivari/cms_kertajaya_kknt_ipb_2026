<?php

use App\Filament\Resources\GalleryAlbums\Pages\CreateGalleryAlbum;
use App\Filament\Resources\GalleryAlbums\Pages\ListGalleryAlbums;
use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\News\Pages\ListNews;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\AdminTable;
use App\Filament\Support\MediaThumbnail;
use App\Models\Admin;
use App\Models\GalleryAlbum;
use App\Models\GalleryAlbumItem;
use App\Models\Media;
use App\Models\MediaDerivative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can render responsive gallery and media management contracts', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)->test(ListGalleryAlbums::class)
        ->assertStatus(200)
        ->assertSee('Album Galeri')
        ->assertSee('Buat Album')
        ->assertTableColumnExists('album_mobile')
        ->assertTableColumnExists('cover_thumbnail')
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('items_count')
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit')
        ->assertTableActionExists('delete');

    Livewire::actingAs($admin)->test(ListMedia::class)
        ->assertStatus(200)
        ->assertSee('Perpustakaan Media')
        ->assertSee('Unggah Media')
        ->assertTableColumnExists('thumbnail')
        ->assertTableColumnExists('original_filename')
        ->assertTableColumnExists('processing_status')
        ->assertTableActionExists('edit')
        ->assertTableActionExists('verify')
        ->assertTableActionExists('reprocess')
        ->assertTableActionExists('delete');
});

test('designed resource tables share export toolbar controls without a column manager or zero filter badge', function () {
    $admin = Admin::factory()->create();

    foreach ([ListNews::class, ListPages::class, ListGalleryAlbums::class, ListMedia::class] as $listPage) {
        $table = Livewire::actingAs($admin)->test($listPage)->instance()->getTable();

        expect($table->getExtraAttributeBag()->get('class'))->toContain('admin-table-shell')
            ->and($table->getFiltersTriggerAction()->getView())->toBe('filament.tables.actions.filter-trigger')
            ->and($table->hasColumnManager())->toBeFalse()
            ->and($table->hasAction('exportCsv'))->toBeTrue()
            ->and($table->hasAction('exportXlsx'))->toBeTrue()
            ->and($table->hasAction('exportPdf'))->toBeTrue();
    }

    expect(AdminTable::filterBadge('0'))->toBeNull()
        ->and(AdminTable::filterBadge(null))->toBeNull()
        ->and(AdminTable::filterBadge('12'))->toBe('12');
});

test('guest stays outside gallery and media administration', function () {
    $this->get(route('filament.admin.resources.gallery-albums.index'))->assertRedirect();
    $this->get(route('filament.admin.resources.media.index'))->assertRedirect();
});

test('gallery form keeps technical fields hidden and preserves lifecycle defaults', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)->test(CreateGalleryAlbum::class)
        ->assertStatus(200)
        ->assertSee('Informasi Album')
        ->assertSee('Foto Galeri')
        ->assertSee('Publikasi')
        ->assertSee('Gambar Sampul')
        ->assertFormFieldDoesNotExist('slug')
        ->assertFormFieldDoesNotExist('published_at')
        ->assertFormFieldExists('items')
        ->assertFormFieldExists('cover_media_id')
        ->assertFormFieldExists('cover_preview')
        ->assertFormFieldExists('is_featured', fn ($field): bool => ! $field->isRequired())
        ->assertFormSet([
            'status' => 'draft',
            'is_featured' => false,
            'items' => [],
        ]);
});

test('an empty gallery album saves without creating an empty gallery item', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)->test(CreateGalleryAlbum::class)
        ->fillForm([
            'title' => 'Album Tanpa Foto',
            'status' => 'draft',
            'is_featured' => false,
            'items' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $album = GalleryAlbum::query()->where('title', 'Album Tanpa Foto')->firstOrFail();

    expect($album->items()->count())->toBe(0)
        ->and(GalleryAlbumItem::query()->where('gallery_album_id', $album->id)->exists())->toBeFalse();
});

test('gallery items retain their media relation and position', function () {
    $album = GalleryAlbum::create(['title' => 'Dokumentasi Desa', 'status' => 'draft']);
    $media = Media::factory()->create();
    $item = GalleryAlbumItem::create([
        'gallery_album_id' => $album->id,
        'media_id' => $media->id,
        'caption' => 'Kegiatan warga',
        'position' => 3,
    ]);

    expect($album->items()->first()->is($item))->toBeTrue()
        ->and($item->media->is($media))->toBeTrue()
        ->and($item->position)->toBe(3);
});

test('media upload keeps its private MIME and size security contract', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)->test(CreateMedia::class)
        ->assertStatus(200)
        ->assertFormFieldExists('file', function ($field): bool {
            return $field->getDiskName() === 'private'
                && $field->getVisibility() === 'private'
                && $field->getMaxSize() === 10240
                && $field->getAcceptedFileTypes() === ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
                && (! $field->isDownloadable())
                && (! $field->isOpenable());
        })
        ->assertFormFieldExists('original_filename')
        ->assertFormFieldDoesNotExist('filename')
        ->assertFormFieldDoesNotExist('directory')
        ->assertFormFieldDoesNotExist('processing_status')
        ->assertFormFieldDoesNotExist('invisible_watermark_status');
});

test('thumbnail presenter only exposes an existing verified public image derivative', function () {
    Storage::fake('public');
    Storage::fake('private');

    $pending = Media::factory()->create();
    $pendingState = [$pending->processing_status, $pending->invisible_watermark_status];

    expect(MediaThumbnail::path($pending))->toBeNull();

    $verified = Media::factory()->create([
        'processing_status' => 'completed',
        'invisible_watermark_status' => 'verified',
    ]);
    $derivative = MediaDerivative::create([
        'media_id' => $verified->id,
        'derivative_type' => 'public',
        'filename' => 'media/verified.jpg',
        'disk' => 'public',
        'size' => 128,
        'mime_type' => 'image/jpeg',
    ]);
    Storage::disk('public')->put($derivative->filename, 'verified-image');
    $verified->load('derivatives');

    expect(MediaThumbnail::path($verified))->toBe('media/verified.jpg')
        ->and(MediaThumbnail::disk($verified))->toBe('public')
        ->and([$pending->fresh()->processing_status, $pending->fresh()->invisible_watermark_status])->toBe($pendingState);
});
