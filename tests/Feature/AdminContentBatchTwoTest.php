<?php

use App\Filament\Resources\News\Pages\CreateNews;
use App\Filament\Resources\News\Pages\EditNews;
use App\Filament\Resources\News\Pages\ListNews;
use App\Filament\Resources\NewsCategories\NewsCategoryResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Admin;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can render the news and page management screens with Indonesian labels', function () {
    $admin = Admin::factory()->create();
    $news = News::create([
        'title' => 'Kegiatan Desa',
        'content' => '<p>Isi kegiatan desa.</p>',
        'status' => 'draft',
    ]);
    $page = Page::create([
        'title' => 'Profil Desa',
        'status' => 'draft',
    ]);

    Livewire::actingAs($admin)->test(ListNews::class)
        ->assertStatus(200)
        ->assertSee('Daftar Berita')
        ->assertSee('Buat Berita')
        ->assertTableColumnExists('featured_media_thumbnail')
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('status')
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit')
        ->assertTableActionExists('delete');

    Livewire::actingAs($admin)->test(CreateNews::class)
        ->assertStatus(200)
        ->assertSee('Konten Utama')
        ->assertSee('Klasifikasi')
        ->assertSee('Publikasi')
        ->assertSee('Pengaturan Lanjutan');

    Livewire::actingAs($admin)->test(EditNews::class, ['record' => $news->getRouteKey()])
        ->assertStatus(200)
        ->assertSee('Ubah Berita');

    Livewire::actingAs($admin)->test(ListPages::class)
        ->assertStatus(200)
        ->assertSee('Daftar Halaman')
        ->assertSee('Buat Halaman')
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('status')
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit')
        ->assertTableActionExists('delete');

    Livewire::actingAs($admin)->test(CreatePage::class)
        ->assertStatus(200)
        ->assertSee('Informasi Halaman')
        ->assertSee('Penyusun Halaman')
        ->assertSee('Publikasi')
        ->assertSee('Pengaturan Lanjutan');

    Livewire::actingAs($admin)->test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertStatus(200)
        ->assertSee('Ubah Halaman');
});

test('guest cannot access news page or category administration', function () {
    $this->get(route('filament.admin.resources.news.index'))->assertRedirect();
    $this->get(route('filament.admin.resources.pages.index'))->assertRedirect();
    $this->get(route('filament.admin.resources.news-categories.index'))->assertRedirect();
});

test('technical slug and publication timestamp fields stay out of content forms', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)->test(CreateNews::class)
        ->assertFormFieldDoesNotExist('slug')
        ->assertFormFieldDoesNotExist('published_at')
        ->assertFormFieldExists('status')
        ->assertFormFieldExists('is_featured', fn ($field): bool => ! $field->isRequired())
        ->assertFormFieldExists('seo_title')
        ->assertFormFieldExists('seo_description')
        ->assertFormSet([
            'status' => 'draft',
            'is_featured' => false,
        ]);

    Livewire::actingAs($admin)->test(CreatePage::class)
        ->assertFormFieldDoesNotExist('slug')
        ->assertFormFieldDoesNotExist('published_at')
        ->assertFormFieldExists('status')
        ->assertFormFieldExists('builder_sections')
        ->assertFormFieldExists('seo_title')
        ->assertFormFieldExists('seo_description');
});

test('news form accepts a non featured draft', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)->test(CreateNews::class)
        ->fillForm([
            'title' => 'Berita Draf Biasa',
            'content' => '<p>Isi berita draf.</p>',
            'status' => 'draft',
            'is_featured' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $news = News::query()->where('title', 'Berita Draf Biasa')->firstOrFail();

    expect($news->status)->toBe('draft')
        ->and($news->is_featured)->toBeFalse()
        ->and($news->published_at)->toBeNull();
});

test('news category can be created contextually without creating a news record', function () {
    $admin = Admin::factory()->create();
    $newsCount = News::count();

    $component = Livewire::actingAs($admin)->test(CreateNews::class)
        ->assertFormComponentActionExists('news_category_id', 'createOption')
        ->mountFormComponentAction('news_category_id', 'createOption')
        ->setFormComponentActionData([
            'name' => 'Pelayanan Warga',
            'description' => 'Informasi mengenai layanan untuk warga desa.',
        ])
        ->callMountedFormComponentAction()
        ->assertHasNoFormComponentActionErrors();

    $category = NewsCategory::query()->where('name', 'Pelayanan Warga')->firstOrFail();

    $component->assertFormSet(['news_category_id' => $category->id]);

    expect($category->slug)->toBe('pelayanan-warga')
        ->and(News::count())->toBe($newsCount);
});

test('page advanced search settings save without exposing technical fields', function () {
    $admin = Admin::factory()->create();

    Livewire::actingAs($admin)->test(CreatePage::class)
        ->fillForm([
            'template' => 'blank',
            'title' => 'Layanan Administrasi',
            'excerpt' => 'Informasi layanan administrasi desa.',
            'builder_sections' => [],
            'status' => 'draft',
            'seo_title' => 'Layanan Administrasi Desa',
            'seo_description' => 'Panduan layanan administrasi Desa Kertajaya.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::query()->where('title', 'Layanan Administrasi')->firstOrFail();

    expect($page->slug)->toBe('layanan-administrasi')
        ->and($page->seo_title)->toBe('Layanan Administrasi Desa')
        ->and($page->seo_description)->toBe('Panduan layanan administrasi Desa Kertajaya.')
        ->and($page->published_at)->toBeNull();
});

test('page publication time is automatic stable and hidden again when returned to draft', function () {
    $page = Page::create([
        'title' => 'Informasi Publikasi',
        'status' => 'draft',
    ]);

    expect($page->published_at)->toBeNull();

    $page->update(['status' => 'published']);
    $page->refresh();
    $publishedAt = $page->published_at->copy();

    $page->update(['title' => 'Informasi Publikasi Diperbarui']);
    $page->refresh();

    expect($page->published_at->equalTo($publishedAt))->toBeTrue();

    $page->update(['status' => 'draft']);

    $this->get(route('pages.show', $page->slug))->assertNotFound();
});

test('opening edit forms does not mutate content and preview actions remain unregistered', function () {
    $admin = Admin::factory()->create();
    $news = News::create([
        'title' => 'Berita Tetap',
        'content' => '<p>Isi berita tetap.</p>',
        'status' => 'published',
    ])->refresh();
    $page = Page::create([
        'title' => 'Halaman Tetap',
        'status' => 'published',
        'seo_title' => 'Judul SEO Tetap',
    ])->refresh();

    $newsState = [
        $news->title,
        $news->slug,
        $news->status,
        $news->published_at?->format('Y-m-d H:i:s.u'),
    ];
    $pageState = [
        $page->title,
        $page->slug,
        $page->status->value,
        $page->published_at?->format('Y-m-d H:i:s.u'),
        $page->seo_title,
    ];
    $preview = TestAction::make('preview')->schemaComponent('form-actions', schema: 'content');

    Livewire::actingAs($admin)->test(CreateNews::class)->assertActionDoesNotExist($preview);
    Livewire::actingAs($admin)->test(CreatePage::class)->assertActionDoesNotExist($preview);
    Livewire::actingAs($admin)->test(EditNews::class, ['record' => $news->getRouteKey()])
        ->assertActionDoesNotExist($preview)
        ->assertActionVisible('website');
    Livewire::actingAs($admin)->test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertActionDoesNotExist($preview)
        ->assertActionVisible('website');

    $freshNews = $news->fresh();
    $freshPage = $page->fresh();

    expect([
        $freshNews->title,
        $freshNews->slug,
        $freshNews->status,
        $freshNews->published_at?->format('Y-m-d H:i:s.u'),
    ])->toBe($newsState)
        ->and([
            $freshPage->title,
            $freshPage->slug,
            $freshPage->status->value,
            $freshPage->published_at?->format('Y-m-d H:i:s.u'),
            $freshPage->seo_title,
        ])->toBe($pageState);
});

test('news categories stay contextual while their protected resource remains available', function () {
    $admin = Admin::factory()->create([
        'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
    ]);
    $this->actingAs($admin);

    expect(NewsCategoryResource::shouldRegisterNavigation())->toBeFalse()
        ->and(NewsCategoryResource::canCreate())->toBeTrue();

    $this->actingAs($admin)
        ->withSession(['session_created_at' => time()])
        ->get(route('filament.admin.resources.news-categories.index'))
        ->assertOk()
        ->assertSee('Kategori Berita');
});
