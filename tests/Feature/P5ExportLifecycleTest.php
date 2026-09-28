<?php

namespace Tests\Feature;

use App\Filament\Exports\NewsExporter;
use App\Filament\Resources\News\Pages\ListNews;
use App\Models\Admin;
use App\Models\News;
use App\Services\AdminTablePdfExportService;
use App\Services\AdminExportCleanupService;
use App\Jobs\Exports\ExportAdminCsvChunk;
use App\Jobs\Exports\PrepareAdminCsvExport;
use App\Jobs\Exports\CreateAdminXlsxFile;
use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class P5ExportLifecycleTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('admin_exports');
        Storage::fake('local');
        config(['queue.default' => 'database']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->withSession(['session_created_at' => time()]);
    }

    public function test_queued_csv_is_private_pending_then_verified_before_download(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        News::create(['title' => '=SUM(1,2)', 'content' => '<p>Isi</p>', 'status' => 'draft']);

        Livewire::actingAs($admin)->test(ListNews::class)->callTableAction('exportCsv');
        $export = Export::query()->firstOrFail();
        $this->assertSame('pending', $export->lifecycle_state);
        $this->assertNull($export->completed_at);
        $url = $this->downloadUrl($export, 'csv');
        $this->actingAs($admin)->get($url)->assertNotFound();

        $this->workDisposableQueue();
        $export->refresh();
        $this->assertSame('completed', $export->lifecycle_state);
        $this->assertNotNull($export->verified_at);
        $this->assertSame(1, $export->successful_rows);
        $this->assertSame(1, DB::table('export_chunks')->where('export_id', $export->id)->count());
        $this->assertSame(1, DB::table('notifications')->count());
        $this->assertSame(1, DB::table('audit_logs')->where('event_type', 'export_completed')->count());
        $this->assertTrue(Storage::disk('admin_exports')->exists($export->artifact_path));

        $response = $this->actingAs($admin)->get($url)->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString("'=SUM(1,2)", $response->streamedContent());
        Storage::disk('admin_exports')->delete($export->artifact_path);
        $this->get($url)->assertNotFound();
    }

    public function test_queued_xlsx_is_a_verified_workbook_and_has_correct_mime(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        News::create(['title' => 'Berita Excel', 'content' => '<p>Isi</p>', 'status' => 'draft']);

        Livewire::actingAs($admin)->test(ListNews::class)->callTableAction('exportXlsx');
        $export = Export::query()->firstOrFail();
        $this->workDisposableQueue();
        $export->refresh();
        $this->assertSame('completed', $export->lifecycle_state);
        $this->assertSame('xlsx', $export->requested_format);
        $this->assertTrue(Storage::disk('admin_exports')->exists($export->artifact_path));
        $this->actingAs($admin)->get($this->downloadUrl($export, 'xlsx'))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pdf_completes_only_after_private_bytes_verify(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        News::create(['title' => 'Berita PDF', 'content' => '<p>Isi</p>', 'status' => 'draft']);
        $service = app(AdminTablePdfExportService::class);
        $export = $service->storeForDownload(News::query(), NewsExporter::class, $admin);

        $this->assertSame('completed', $export->lifecycle_state);
        $this->assertSame('pdf', $export->requested_format);
        $this->assertNotNull($export->artifact_sha256);
        $this->actingAs($admin)->get($service->temporaryDownloadUrl($export))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_owner_expiry_state_and_tampered_bytes_block_pdf_download(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        News::create(['title' => 'Rahasia', 'content' => '<p>Isi</p>', 'status' => 'draft']);
        $service = app(AdminTablePdfExportService::class);
        $export = $service->storeForDownload(News::query(), NewsExporter::class, $admin);
        $url = $service->temporaryDownloadUrl($export);
        $other = (new Admin())->forceFill([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
            'force_password_change' => false,
        ]);

        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($admin);
        $export->forceFill(['lifecycle_state' => 'failed'])->save();
        $this->get($url)->assertNotFound();
        $export->forceFill(['lifecycle_state' => 'completed', 'completed_at' => now()->subHours(25)])->save();
        $this->get($url)->assertNotFound();
        $export->forceFill(['completed_at' => now()])->save();
        Storage::disk('admin_exports')->put($export->artifact_path, 'not a PDF');
        $this->get($url)->assertNotFound();
    }

    public function test_cleanup_is_state_aware_and_unsafe_files_leave_retryable_state(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        News::create(['title' => 'Laporan lama', 'content' => '<p>Isi</p>', 'status' => 'draft']);
        $completed = app(AdminTablePdfExportService::class)
            ->storeForDownload(News::query(), NewsExporter::class, $admin);
        $completed->forceFill(['completed_at' => now()->subHours(25)])->save();
        $pending = Export::create([
            'file_disk' => 'admin_exports', 'file_name' => 'pending', 'exporter' => NewsExporter::class,
            'total_rows' => 1, 'user_id' => $admin->id, 'lifecycle_state' => 'pending',
            'created_at' => now()->subHours(25),
        ]);
        $disk = Storage::disk('admin_exports');
        $disk->put($pending->getFileDirectory().'/pending.csv', 'private fixture');
        $disk->put($completed->getFileDirectory().'/unknown.bin', 'not owned by lifecycle');
        $disk->put('filament_exports/unrelated/unrelated.bin', 'untouched');

        $this->assertSame(0, app(AdminExportCleanupService::class)->pruneExpired());
        $this->assertSame('cleanup_failed', $completed->fresh()->lifecycle_state);
        $this->assertTrue($disk->exists($completed->getFileDirectory().'/unknown.bin'));
        $this->assertTrue($disk->exists($pending->getFileDirectory().'/pending.csv'));

        $failed = Export::create([
            'file_disk' => 'admin_exports', 'file_name' => 'gagal', 'exporter' => NewsExporter::class,
            'total_rows' => 1, 'user_id' => $admin->id, 'lifecycle_state' => 'failed',
        ]);
        $disk->put($failed->getFileDirectory().'/gagal.csv.pending', 'private candidate');
        $failed->forceFill(['updated_at' => now()->subHours(25)])->saveQuietly();
        $disk->delete($completed->getFileDirectory().'/unknown.bin');
        $this->assertSame(2, app(AdminExportCleanupService::class)->pruneExpired());
        $this->assertNull($completed->fresh());
        $this->assertNull($failed->fresh());
        $this->assertNotNull($pending->fresh());
        $this->assertTrue($disk->exists('filament_exports/unrelated/unrelated.bin'));
    }

    public function test_repeated_chunk_does_not_repeat_counters_or_rows(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $news = News::create(['title' => 'Sekali', 'content' => '<p>Isi</p>', 'status' => 'draft']);
        Livewire::actingAs($admin)->test(ListNews::class)->callTableAction('exportCsv');
        $export = Export::query()->firstOrFail();
        $this->assertSame('village_cms_test', config('database.connections.pgsql.database'));
        for ($attempt = 0; $attempt < 10 && ! DB::table('export_chunks')->where('export_id', $export->id)->exists(); $attempt++) {
            Artisan::call('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 1]);
        }
        $export->refresh();
        $this->assertSame('generating', $export->lifecycle_state);
        $this->assertSame(1, $export->successful_rows);

        $columns = collect(NewsExporter::getColumns())
            ->mapWithKeys(fn ($column): array => [$column->getName() => (string) $column->getLabel()])->all();
        (new ExportAdminCsvChunk(
            $export, EloquentSerializeFacade::serialize(News::query()), [$news->id], 1,
            $columns, ['p5_format' => 'csv'],
        ))->handle();

        $this->assertSame(1, $export->fresh()->successful_rows);
        $this->assertSame(1, DB::table('export_chunks')->where('export_id', $export->id)->count());
        $this->workDisposableQueue();
        $this->assertSame('completed', $export->fresh()->lifecycle_state);
        $this->assertSame(1, DB::table('notifications')->count());
        $this->assertSame(1, DB::table('audit_logs')->where('event_type', 'export_completed')->count());
    }

    public function test_exhausted_export_jobs_leave_a_failed_state_without_duplicate_audit(): void
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        foreach ([PrepareAdminCsvExport::class, ExportAdminCsvChunk::class, CreateAdminXlsxFile::class] as $jobClass) {
            $export = Export::create([
                'file_disk' => 'admin_exports', 'file_name' => 'failed'.uniqid(),
                'exporter' => NewsExporter::class, 'total_rows' => 1, 'user_id' => $admin->id,
                'requested_format' => 'csv', 'lifecycle_state' => 'generating',
            ]);
            $columns = ['title' => 'Judul'];
            $job = match ($jobClass) {
                PrepareAdminCsvExport::class => new PrepareAdminCsvExport($export, '', $columns),
                ExportAdminCsvChunk::class => new ExportAdminCsvChunk($export, '', [], 1, $columns),
                CreateAdminXlsxFile::class => new CreateAdminXlsxFile($export, $columns),
            };

            $job->failed(new \RuntimeException('Disposable job failure'));
            $job->failed(new \RuntimeException('Duplicate failure callback'));

            $this->assertSame('failed', $export->fresh()->lifecycle_state);
            $this->assertSame(1, DB::table('audit_logs')->where('event_type', 'export_failed')
                ->where('subject_id', (string) $export->id)->count());
        }
    }

    private function downloadUrl(Export $export, string $format): string
    {
        return URL::signedRoute('filament.exports.download', [
            'authGuard' => 'web', 'export' => $export, 'format' => $format,
        ], absolute: false);
    }

    private function workDisposableQueue(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('village_cms_test', config('database.connections.pgsql.database'));
        $this->assertSame('database', config('queue.default'));

        for ($attempt = 0; $attempt < 20 && DB::table('jobs')->exists(); $attempt++) {
            Artisan::call('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 1]);
        }
        $this->assertSame(0, DB::table('jobs')->count(), Artisan::output());
        $this->assertSame(0, DB::table('failed_jobs')->count(), Artisan::output());
    }
}
