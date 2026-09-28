<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Admin;
use App\Models\AuditLog;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AdminMfaRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database', 'session.connection' => null]);
        config(['services.turnstile.secret' => 'disposable-recovery-test-secret']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function enrolledAdmin(): Admin
    {
        return Admin::factory()->create([
            'password' => Hash::make('KnownPassword12'),
            'password_changed_at' => now()->subDay(),
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]);
    }

    private function recover(Admin $admin): void
    {
        $this->artisan('admin:recover-mfa', ['email' => $admin->email, '--force' => true])
            ->assertExitCode(0);
    }

    public function test_operator_recovery_clears_only_mfa_revokes_sessions_and_audits_without_secrets(): void
    {
        $admin = $this->enrolledAdmin();
        $passwordHash = $admin->password;
        $passwordChangedAt = $admin->password_changed_at;
        $oldSecret = $admin->getAppAuthenticationSecret();
        DB::table('sessions')->insert([
            'id' => 'disposable-admin-session', 'user_id' => $admin->id,
            'payload' => base64_encode('disposable'), 'last_activity' => time(),
        ]);

        $this->recover($admin);

        $admin->refresh();
        $this->assertNull($admin->getAppAuthenticationSecret());
        $this->assertSame(1, $admin->mfa_recovery_version);
        $this->assertSame($passwordHash, $admin->password);
        $this->assertEquals($passwordChangedAt, $admin->password_changed_at);
        $this->assertTrue(Hash::check('KnownPassword12', $admin->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'disposable-admin-session']);

        $events = AuditLog::where('event_type', 'admin_mfa_recovery')->get();
        $this->assertCount(1, $events);
        $this->assertNull($events->first()->admin_id);
        $this->assertSame($admin->id, $events->first()->subject_id);
        $this->assertSame(['source' => 'cli_operator', 'outcome' => 'success'], $events->first()->new_values);
        $this->assertStringNotContainsString($oldSecret, $events->first()->toJson());
        $this->assertStringNotContainsString($passwordHash, $events->first()->toJson());
    }

    public function test_recovery_refuses_zero_anomalous_and_mismatched_admin_states(): void
    {
        $this->artisan('admin:recover-mfa', ['email' => 'missing@example.test', '--force' => true])
            ->assertExitCode(1);

        $admin = $this->enrolledAdmin();
        $this->artisan('admin:recover-mfa', ['email' => 'wrong@example.test', '--force' => true])
            ->assertExitCode(1);
        $this->assertNotNull($admin->fresh()->getAppAuthenticationSecret());

        DB::table('admins')->insert([
            'id' => (string) Str::uuid(), 'name' => 'Anomalous Admin',
            'username' => 'second-admin', 'email' => 'second@example.test',
            'password' => Hash::make('SecondPassword12'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->artisan('admin:recover-mfa', ['email' => $admin->email, '--force' => true])
            ->assertExitCode(1);
        $this->assertNotNull($admin->fresh()->getAppAuthenticationSecret());
        $this->assertSame(0, AuditLog::where('event_type', 'admin_mfa_recovery')->count());
    }

    public function test_declined_confirmation_and_unsupported_session_backend_change_nothing(): void
    {
        $admin = $this->enrolledAdmin();
        $this->artisan('admin:recover-mfa', ['email' => $admin->email])
            ->expectsQuestion('Continue with authorized MFA recovery?', false)
            ->assertExitCode(1);
        config(['session.driver' => 'array']);
        $this->artisan('admin:recover-mfa', ['email' => $admin->email, '--force' => true])
            ->assertExitCode(1);
        $this->assertNotNull($admin->fresh()->getAppAuthenticationSecret());
        $this->assertSame(0, AuditLog::where('event_type', 'admin_mfa_recovery')->count());
    }

    public function test_audit_write_failure_rolls_back_mfa_version_and_session_deletion(): void
    {
        $admin = $this->enrolledAdmin();
        DB::table('sessions')->insert([
            'id' => 'rollback-session', 'user_id' => $admin->id,
            'payload' => base64_encode('disposable'), 'last_activity' => time(),
        ]);
        AuditLog::creating(function (AuditLog $log): void {
            if ($log->event_type === 'admin_mfa_recovery') {
                throw new RuntimeException('Injected audit failure');
            }
        });

        $this->artisan('admin:recover-mfa', ['email' => $admin->email, '--force' => true])
            ->assertExitCode(1);

        $this->assertNotNull($admin->fresh()->getAppAuthenticationSecret());
        $this->assertSame(0, $admin->fresh()->mfa_recovery_version);
        $this->assertDatabaseHas('sessions', ['id' => 'rollback-session']);
        $this->assertSame(0, AuditLog::where('event_type', 'admin_mfa_recovery')->count());
    }

    public function test_stale_session_is_rejected(): void
    {
        $admin = $this->enrolledAdmin();
        $this->recover($admin);

        $this->actingAs($admin->fresh())->withSession([
            'admin_mfa_recovery_version' => 0,
            'session_created_at' => time(),
        ]);
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest('web');
    }

    public function test_normal_password_login_requires_reenrollment_after_recovery(): void
    {
        $admin = $this->enrolledAdmin();
        $this->recover($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);
        $login = Livewire::test(Login::class);
        $login->fillForm([
            'username' => $admin->username,
            'password' => 'KnownPassword12',
            'captcha' => 'disposable-token',
        ])->call('authenticate');
        $this->assertAuthenticatedAs($admin->fresh(), 'web');
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(Filament::getSetUpRequiredMultiFactorAuthenticationUrl());
        $this->get(Filament::getSetUpRequiredMultiFactorAuthenticationUrl())->assertOk();

        // The normal Filament provider owns secret creation and persistence.
        $newSecret = AppAuthentication::make()->generateSecret();
        AppAuthentication::make()->saveSecret(Filament::auth()->user(), $newSecret);
        $this->assertSame($newSecret, $admin->fresh()->getAppAuthenticationSecret());
        $this->get(route('filament.admin.pages.dashboard'))->assertOk();
    }
}
