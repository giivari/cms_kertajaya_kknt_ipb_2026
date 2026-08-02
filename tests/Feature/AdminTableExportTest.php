<?php

use App\Filament\Exports\AuditLogExporter;
use App\Filament\Exports\ContactMessageExporter;
use App\Filament\Exports\DocumentCategoryExporter;
use App\Filament\Exports\DocumentExporter;
use App\Filament\Exports\GalleryAlbumExporter;
use App\Filament\Exports\LocationCategoryExporter;
use App\Filament\Exports\LocationExporter;
use App\Filament\Exports\MediaExporter;
use App\Filament\Exports\MenuExporter;
use App\Filament\Exports\NewsCategoryExporter;
use App\Filament\Exports\NewsExporter;
use App\Filament\Exports\PageExporter;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\ContactMessageResource\Pages\ListContactMessages;
use App\Filament\Resources\DocumentCategories\Pages\ListDocumentCategories;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\GalleryAlbums\Pages\ListGalleryAlbums;
use App\Filament\Resources\LocationCategories\Pages\ListLocationCategories;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\News\Pages\ListNews;
use App\Filament\Resources\NewsCategories\Pages\ListNewsCategories;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\News;
use App\Services\AdminExportCleanupService;
use App\Services\AdminTablePdfExportService;
use App\Support\Exports\ExportValueSanitizer;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('all real admin tables remove the column manager and register the three export formats', function () {
    $admin = Admin::factory()->create();
    $pages = [
        ListNews::class,
        ListPages::class,
        ListNewsCategories::class,
        ListGalleryAlbums::class,
        ListMedia::class,
        ListDocuments::class,
        ListDocumentCategories::class,
        ListLocations::class,
        ListLocationCategories::class,
        ListMenus::class,
        ListContactMessages::class,
        ListAuditLogs::class,
    ];

    foreach ($pages as $page) {
        $table = Livewire::actingAs($admin)->test($page)->instance()->getTable();

        expect($table->hasColumnManager())->toBeFalse()
            ->and(collect($table->getColumns())->contains(fn ($column): bool => $column->isToggleable()))->toBeFalse()
            ->and($table->getExtraAttributeBag()->get('class'))->toContain('admin-table-shell')
            ->and($table->hasAction('exportCsv'))->toBeTrue()
            ->and($table->hasAction('exportXlsx'))->toBeTrue()
            ->and($table->hasAction('exportPdf'))->toBeTrue();

        $pdfActionGroup = $table->getAction('exportPdf')->getGroup();

        expect($table->getFiltersTriggerAction()->getExtraAttributeBag()->get('class'))->toContain('admin-table-filter-trigger')
            ->and($pdfActionGroup)->not->toBeNull()
            ->and($pdfActionGroup->getExtraDropdownAttributeBag()->get('class'))->toContain('admin-table-export-control');

        $csv = $table->getAction('exportCsv');
        $xlsx = $table->getAction('exportXlsx');

        expect($csv)->toBeInstanceOf(ExportAction::class)
            ->and($csv->hasColumnMapping())->toBeFalse()
            ->and($csv->getFormats())->toBe([ExportFormat::Csv])
            ->and($csv->getMaxRows())->toBe(10_000)
            ->and($csv->getFileDisk())->toBe('local')
            ->and($xlsx)->toBeInstanceOf(ExportAction::class)
            ->and($xlsx->hasColumnMapping())->toBeFalse()
            ->and($xlsx->getFormats())->toBe([ExportFormat::Xlsx]);
    }
});

test('every exporter uses an explicit safe allowlist', function () {
    $exporters = [
        NewsExporter::class,
        PageExporter::class,
        NewsCategoryExporter::class,
        GalleryAlbumExporter::class,
        MediaExporter::class,
        DocumentExporter::class,
        DocumentCategoryExporter::class,
        LocationExporter::class,
        LocationCategoryExporter::class,
        MenuExporter::class,
        ContactMessageExporter::class,
        AuditLogExporter::class,
    ];
    $forbidden = [
        'password', 'app_authentication_secret', 'remember_token', 'session_id',
        'token_hash', 'encrypted_payload', 'disk', 'directory', 'filename',
        'checksum', 'metadata', 'old_values', 'new_values', 'user_agent',
    ];

    foreach ($exporters as $exporter) {
        $columnNames = collect($exporter::getColumns())->map->getName();

        expect($columnNames)->not->toBeEmpty();

        foreach ($forbidden as $attribute) {
            expect($columnNames)->not->toContain($attribute);
        }
    }
});

test('spreadsheet text sanitizer prevents formula injection without mutating source values', function () {
    foreach (['=HYPERLINK("https://example.test")', '+SUM(1,2)', '-1+1', '@command'] as $formula) {
        expect(ExportValueSanitizer::text($formula))->toBe("'{$formula}");
    }

    expect(ExportValueSanitizer::text('Informasi aman'))->toBe('Informasi aman');

    $news = new News([
        'title' => '=HYPERLINK("https://example.test")',
        'excerpt' => '+SUM(1,2)',
        'status' => 'draft',
    ]);
    $columns = collect(NewsExporter::getColumns());
    $columnMap = $columns->mapWithKeys(fn ($column): array => [$column->getName() => (string) $column->getLabel()])->all();
    $exporter = (new Export([
        'file_disk' => 'local',
        'exporter' => NewsExporter::class,
        'total_rows' => 1,
    ]))->getExporter($columnMap, []);
    $row = $exporter($news);

    expect($row[0])->toBe("'=HYPERLINK(\"https://example.test\")")
        ->and($row[2])->toBe("'+SUM(1,2)")
        ->and($news->title)->toBe('=HYPERLINK("https://example.test")');
});

test('csv and xlsx exports use the filtered table query and private disk', function () {
    Storage::fake('local');
    $admin = Admin::factory()->create();
    News::create(['title' => 'Berita Sasaran', 'content' => '<p>Aman</p>', 'status' => 'draft']);
    News::create(['title' => 'Berita Lain', 'content' => '<p>Aman</p>', 'status' => 'draft']);
    $newsCount = News::count();

    Livewire::actingAs($admin)
        ->test(ListNews::class)
        ->searchTable('Sasaran')
        ->callTableAction('exportCsv');

    $csvExport = Export::query()->latest('id')->firstOrFail();
    $csvFiles = Storage::disk('local')->files($csvExport->getFileDirectory());
    $csvBody = collect($csvFiles)
        ->filter(fn (string $file): bool => str_ends_with($file, '.csv') && ! str_ends_with($file, 'headers.csv'))
        ->map(fn (string $file): string => Storage::disk('local')->get($file))
        ->implode("\n");

    expect($csvExport->user->is($admin))->toBeTrue()
        ->and($csvExport->file_disk)->toBe('local')
        ->and($csvBody)->toContain('Berita Sasaran')
        ->and($csvBody)->not->toContain('Berita Lain');

    Livewire::actingAs($admin)
        ->test(ListNews::class)
        ->searchTable('Sasaran')
        ->callTableAction('exportXlsx');

    $xlsxExport = Export::query()->latest('id')->firstOrFail();

    expect(Storage::disk('local')->exists($xlsxExport->getFileDirectory().'/'.$xlsxExport->file_name.'.xlsx'))->toBeTrue()
        ->and(News::count())->toBe($newsCount);
});

test('table export query preserves sorting and the default soft delete scope', function () {
    $admin = Admin::factory()->create();
    News::create(['title' => 'Zulu', 'content' => '<p>Isi</p>', 'status' => 'draft']);
    News::create(['title' => 'Alfa', 'content' => '<p>Isi</p>', 'status' => 'draft']);
    $deleted = News::create(['title' => 'Dihapus', 'content' => '<p>Isi</p>', 'status' => 'draft']);
    $deleted->delete();

    $component = Livewire::actingAs($admin)
        ->test(ListNews::class)
        ->sortTable('title', 'asc');
    $titles = $component->instance()->getTableQueryForExport()->pluck('title')->all();

    expect($titles)->toBe(['Alfa', 'Zulu'])
        ->and(AdminTablePdfExportService::MAX_ROWS)->toBe(1000);
});

test('pdf export returns a safe pdf response and leaves business records unchanged', function () {
    Admin::factory()->create();
    News::create(['title' => 'Berita PDF', 'content' => '<p>Isi</p>', 'status' => 'draft']);
    $newsCount = News::count();

    $response = app(AdminTablePdfExportService::class)->download(
        News::query()->where('title', 'Berita PDF')->orderBy('title'),
        NewsExporter::class,
    );

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->getContent())->toStartWith('%PDF-')
        ->and(News::count())->toBe($newsCount);
});

test('pdf table action redirects to an owner-only private download instead of returning binary to livewire', function () {
    Storage::fake('local');
    $owner = Admin::factory()->create();
    News::create(['title' => 'Berita PDF Browser', 'content' => '<p>Isi</p>', 'status' => 'draft']);
    $otherAdmin = (new Admin)->forceFill([
        'id' => (string) Str::uuid(),
    ]);
    $newsCount = News::count();
    $auditLogCount = AuditLog::count();

    $component = Livewire::actingAs($owner)
        ->test(ListNews::class)
        ->searchTable('PDF Browser')
        ->callTableAction('exportPdf');

    $export = Export::query()->latest('id')->firstOrFail();
    $service = app(AdminTablePdfExportService::class);
    $path = $service->storedPdfPath($export);
    $url = $service->temporaryDownloadUrl($export);
    $missingUrl = URL::temporarySignedRoute(
        'admin.exports.pdf.download',
        now()->addDay(),
        ['export' => 999_999],
        absolute: false,
    );

    $component->assertRedirectContains(route('admin.exports.pdf.download', ['export' => $export], absolute: false));

    expect($export->user()->is($owner))->toBeTrue()
        ->and($export->user()->is($otherAdmin))->toBeFalse()
        ->and($export->file_disk)->toBe('local')
        ->and($export->file_name)->toMatch('/^[A-Za-z0-9]{48}$/')
        ->and(Storage::disk('local')->exists($path))->toBeTrue()
        ->and(Storage::disk('local')->get($path))->toStartWith('%PDF-')
        ->and(News::count())->toBe($newsCount)
        ->and(AuditLog::count())->toBe($auditLogCount)
        ->and(Admin::count())->toBe(1);

    auth()->logout();
    $this->get($url)->assertRedirect();
    $this->actingAs($otherAdmin)->get($url)->assertForbidden();
    $this->actingAs($owner)->get($missingUrl)->assertNotFound();

    $download = $this->actingAs($owner)->get($url);

    $download
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertDownload(NewsExporter::fileSlug().'-'.now('Asia/Jakarta')->format('Y-m-d').'.pdf');

    expect($download->streamedContent())->toStartWith('%PDF-')
        ->and(News::count())->toBe($newsCount)
        ->and(AuditLog::count())->toBe($auditLogCount);
});

test('cleanup removes only expired export directories and records', function () {
    Storage::fake('local');
    $admin = Admin::factory()->create();

    $expired = Export::create([
        'file_disk' => 'local', 'file_name' => 'lama', 'exporter' => NewsExporter::class,
        'processed_rows' => 1, 'total_rows' => 1, 'successful_rows' => 1, 'user_id' => $admin->id,
        'created_at' => now()->subHours(25), 'updated_at' => now()->subHours(25),
    ]);
    $current = Export::create([
        'file_disk' => 'local', 'file_name' => 'baru', 'exporter' => NewsExporter::class,
        'processed_rows' => 1, 'total_rows' => 1, 'successful_rows' => 1, 'user_id' => $admin->id,
    ]);
    Storage::disk('local')->put($expired->getFileDirectory().'/lama.csv', 'lama');
    Storage::disk('local')->put($current->getFileDirectory().'/baru.csv', 'baru');

    expect(app(AdminExportCleanupService::class)->pruneExpired())->toBe(1)
        ->and(Export::query()->whereKey($expired->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->directoryExists($expired->getFileDirectory()))->toBeFalse()
        ->and(Export::query()->whereKey($current->id)->exists())->toBeTrue()
        ->and(Storage::disk('local')->exists($current->getFileDirectory().'/baru.csv'))->toBeTrue();
});

test('private export download requires its owning admin', function () {
    Storage::fake('local');
    $owner = Admin::factory()->create();
    $otherAdmin = (new Admin)->forceFill([
        'id' => (string) Str::uuid(),
    ]);
    $export = Export::create([
        'file_disk' => 'local', 'file_name' => 'kepemilikan', 'exporter' => NewsExporter::class,
        'processed_rows' => 1, 'total_rows' => 1, 'successful_rows' => 1, 'user_id' => $owner->id,
    ]);
    Storage::disk('local')->put($export->getFileDirectory().'/headers.csv', "Judul\n");
    Storage::disk('local')->put($export->getFileDirectory().'/0000000000000001.csv', "Aman\n");
    $url = URL::signedRoute('filament.exports.download', [
        'authGuard' => 'web',
        'export' => $export,
        'format' => ExportFormat::Csv,
    ], absolute: false);
    $missingUrl = URL::signedRoute('filament.exports.download', [
        'authGuard' => 'web',
        'export' => 999_999,
        'format' => ExportFormat::Csv,
    ], absolute: false);

    expect($export->user()->is($owner))->toBeTrue()
        ->and($export->user()->is($otherAdmin))->toBeFalse()
        ->and(Admin::count())->toBe(1);

    $this->get($url)->assertUnauthorized();
    $this->actingAs($otherAdmin)->get($url)->assertForbidden();
    $this->actingAs($owner)->get($missingUrl)->assertNotFound();
    $this->actingAs($owner)->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('guest cannot reach export-enabled resource tables', function () {
    $this->get(route('filament.admin.resources.news.index'))->assertRedirect();
    $this->get(route('filament.admin.resources.contact-messages.index'))->assertRedirect();
    $this->get(route('filament.admin.resources.audit-logs.index'))->assertRedirect();
});
