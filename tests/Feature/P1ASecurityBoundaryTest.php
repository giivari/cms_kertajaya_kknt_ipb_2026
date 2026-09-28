<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\WebsiteSettings;
use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\WebsiteSetting;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use Tests\TestCase;
use Filament\Actions\Exports\Models\Export;
use App\Filament\Exports\NewsExporter;
use App\Services\AdminTablePdfExportService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

class P1ASecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function admin(): Admin
    {
        return Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]);
    }

    private function snapshot(string $url, string $name): string
    {
        $response = $this->get($url)->assertOk();
        preg_match_all('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        foreach ($matches[1] as $encoded) {
            $snapshot = html_entity_decode($encoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (json_decode($snapshot, true)['memo']['name'] === $name) {
                return $snapshot;
            }
        }
        $this->fail('The actual page did not contain the expected signed Livewire snapshot.');
    }

    private function update(string $snapshot, array $updates = [], array $calls = [])
    {
        // Exercise the actual HTTP endpoint. Livewire::test() alone bypasses
        // PersistentMiddleware's isLivewireRoute() boundary.
        return $this->withHeader('X-Livewire', 'true')->postJson(
            app(HandleRequests::class)->getUpdateUri(),
            ['components' => [['snapshot' => $snapshot, 'updates' => $updates, 'calls' => $calls]]],
        );
    }

    public function test_ordinary_profile_snapshot_excludes_secret_but_provider_can_use_it(): void
    {
        $admin = $this->admin();
        $secret = $admin->getAppAuthenticationSecret();
        $this->assertSame($secret, AppAuthentication::make()->getSecret($admin));
        $this->assertArrayNotHasKey('app_authentication_secret', $admin->attributesToArray());
        $this->assertStringNotContainsString($secret, $admin->toJson());
        $this->actingAs($admin);
        $response = $this->get(EditProfile::getUrl())->assertOk();
        $this->assertStringNotContainsString($secret, $response->getContent());
        $snapshot = $this->snapshot(EditProfile::getUrl(), 'app.filament.pages.auth.edit-profile');
        $this->assertStringNotContainsString('app_authentication_secret', $snapshot);
        $this->assertStringNotContainsString($secret, AuditLog::all()->toJson());
    }

    public function test_livewire_rejects_revoked_password_session_before_settings_mutation(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $snapshot = $this->snapshot(WebsiteSettings::getUrl(), 'app.filament.pages.website-settings');
        $admin->update(['password' => Hash::make('test-only-replaced-password')]);
        Auth::guard('web')->setUser($admin->fresh());
        $this->update($snapshot, ['data.village_name' => 'DENIED_MUTATION'], [
            ['method' => 'save', 'params' => []],
        ])->assertRedirect(route('filament.admin.auth.login'));
        $this->assertNotSame('DENIED_MUTATION', WebsiteSetting::find('village_name')?->value);
    }

    public function test_livewire_rejects_force_password_transition_before_settings_mutation(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $snapshot = $this->snapshot(WebsiteSettings::getUrl(), 'app.filament.pages.website-settings');
        $admin->update(['force_password_change' => true]);
        Auth::guard('web')->setUser($admin->fresh());
        $this->update($snapshot, ['data.village_name' => 'DENIED_MUTATION'], [
            ['method' => 'save', 'params' => []],
        ])->assertRedirect(EditProfile::getUrl());
        $this->assertNotSame('DENIED_MUTATION', WebsiteSetting::find('village_name')?->value);
        $this->get(EditProfile::getUrl())->assertOk();
    }

    public function test_absolute_timeout_also_rejects_actual_livewire_updates(): void
    {
        $this->actingAs($this->admin());
        $snapshot = $this->snapshot(WebsiteSettings::getUrl(), 'app.filament.pages.website-settings');
        $this->withSession(['session_created_at' => time() - 8 * 60 * 60 - 1]);
        $this->update($snapshot, [], [['method' => '$refresh', 'params' => []]])
            ->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest('web');
    }

    public function test_mfa_removal_is_checked_on_an_already_open_livewire_page(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $snapshot = $this->snapshot(WebsiteSettings::getUrl(), 'app.filament.pages.website-settings');
        $admin->saveAppAuthenticationSecret(null);
        Auth::guard('web')->setUser($admin->fresh());
        $this->update($snapshot, [], [['method' => '$refresh', 'params' => []]])
            ->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
    }

    public function test_legacy_and_token_previews_require_mfa_enrollment(): void
    {
        $this->actingAs(Admin::factory()->create());
        $urls = [
            route('pages.preview', 'missing'), route('news.preview', 'missing'),
            route('gallery.preview', 'missing'), route('documents.preview', 'missing'),
            route('admin.preview.show', 'missing'), route('admin.preview.shell', 'missing'),
            route('admin.preview.asset', ['token' => 'missing', 'assetToken' => 'missing']),
        ];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
        }
        // Enrollment and logout remain available during a forced-password transition.
        auth()->user()->update(['force_password_change' => true]);
        $this->get(Filament::getSetUpRequiredMultiFactorAuthenticationUrl())->assertOk();
        $this->post(Filament::getLogoutUrl())->assertRedirect();
        $this->assertGuest();
    }

    public function test_error_responses_receive_headers_without_replacing_preview_csp(): void
    {
        $this->get('/p1a-nonexistent-route')->assertNotFound()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_csv_xlsx_and_signed_pdf_downloads_require_enrollment_and_valid_session(): void
    {
        $admin = Admin::factory()->create();
        $export = Export::create([
            'user_id' => $admin->id, 'file_disk' => 'local', 'file_name' => 'boundary-test',
            'exporter' => NewsExporter::class, 'total_rows' => 0, 'completed_at' => now(),
        ]);
        $urls = [
            route('filament.exports.download', ['export' => $export, 'format' => 'csv']),
            route('filament.exports.download', ['export' => $export, 'format' => 'xlsx']),
            app(AdminTablePdfExportService::class)->temporaryDownloadUrl($export),
        ];
        $this->actingAs($admin);
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
        }

        $admin->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());
        $admin->update(['force_password_change' => true]);
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(EditProfile::getUrl());
        }

        $admin->update(['force_password_change' => false]);
        foreach ($urls as $url) {
            $this->actingAs($admin->fresh())->withSession(['password_hash_web' => 'revoked-test-session']);
            $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
        }
    }

    public function test_eligible_owner_can_still_download_csv_with_security_headers(): void
    {
        $admin = $this->admin();
        $export = Export::create([
            'user_id' => $admin->id, 'file_disk' => 'admin_exports', 'file_name' => 'boundary-test',
            'exporter' => NewsExporter::class, 'total_rows' => 0, 'successful_rows' => 0,
            'requested_format' => 'csv', 'lifecycle_state' => 'completed',
            'completed_at' => now(), 'verified_at' => now(),
        ]);
        $bytes = "\xEF\xBB\xBFtitle\n";
        $path = $export->getFileDirectory().'/boundary-test.csv';
        Storage::disk('admin_exports')->put($path, $bytes);
        $export->forceFill([
            'artifact_path' => $path, 'artifact_size' => strlen($bytes),
            'artifact_sha256' => hash('sha256', $bytes),
        ])->save();
        $url = URL::signedRoute('filament.exports.download', [
            'authGuard' => 'web', 'export' => $export, 'format' => 'csv',
        ], absolute: false);
        $this->actingAs($admin)->get($url)
            ->assertOk()->assertDownload('boundary-test.csv')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_password_audit_is_written_after_success_and_not_after_validation_failure(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        Livewire::test(EditProfile::class)->fillForm([
            'password' => 'Test-only-new-password1', 'passwordConfirmation' => 'Test-only-new-password1',
            'currentPassword' => 'wrong-current-password', 'totp' => 'invalid',
        ])->call('save')->assertHasFormErrors();
        $this->assertSame(0, AuditLog::where('event_type', 'password_changed')->count());

        Livewire::test(EditProfile::class)->fillForm([
            'password' => 'Test-only-new-password1', 'passwordConfirmation' => 'Test-only-new-password1',
            'currentPassword' => 'password', 'totp' => AppAuthentication::make()->getCurrentCode($admin),
        ])->call('save')->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('Test-only-new-password1', $admin->fresh()->password));
        $this->assertSame(1, AuditLog::where('event_type', 'password_changed')->count());
    }

    public function test_password_save_failure_does_not_leave_success_audit(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->withoutExceptionHandling();
        \Illuminate\Support\Facades\Event::listen('eloquent.updating: '.Admin::class, function () {
            throw new \RuntimeException('p1a-simulated-save-failure');
        });
        try {
            Livewire::test(EditProfile::class)->fillForm([
                'password' => 'Test-only-new-password1', 'passwordConfirmation' => 'Test-only-new-password1',
                'currentPassword' => 'password', 'totp' => AppAuthentication::make()->getCurrentCode($admin),
            ])->call('save');
            $this->fail('The simulated persistence failure must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('p1a-simulated-save-failure', $exception->getMessage());
        }
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
        $this->assertSame(0, AuditLog::where('event_type', 'password_changed')->count());
    }

    public function test_old_news_html_is_safe_on_read_without_overwriting_stored_data(): void
    {
        $news = \App\Models\News::create([
            'title' => 'P1A News', 'content' => '<p><strong>Safe formatting</strong></p>',
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        $unsafe = '<p><strong>Safe formatting</strong></p><img src="/missing.png" onerror=alert(123)><script>alert(123)</script>';
        // Test fixture simulates pre-remediation stored HTML, bypassing the new
        // write sanitizer only inside the isolated test database.
        \Illuminate\Support\Facades\DB::table('news')->where('id', $news->id)->update(['content' => $unsafe]);
        $response = $this->get(route('news.show', $news->slug))->assertOk();
        $this->assertStringContainsString('<strong>Safe formatting</strong>', $response->getContent());
        $this->assertStringNotContainsString('onerror', $response->getContent());
        $this->assertStringNotContainsString('alert(123)', $response->getContent());
        $this->assertSame($unsafe, $news->fresh()->content);
    }
}
