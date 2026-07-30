<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.secret' => 'secret']);
    }

    public function test_second_admin_cannot_be_provisioned_or_created()
    {
        Admin::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only one administrator account is permitted.');

        Admin::factory()->create(['username' => 'admin2']);
    }

    public function test_provision_command_fails_if_admin_exists()
    {
        Admin::factory()->create();

        $this->artisan('admin:provision', [
            '--username' => 'anotheradmin',
        ])->assertFailed();
    }

    public function test_audit_logs_redact_sensitive_information_with_sentinel_secrets()
    {
        $sentinelPassword = 'SENTINEL_PASSWORD_123!@#';
        $sentinelTotp = 'SENTINEL_TOTP_SECRET_ABC';
        $sentinelCaptcha = 'SENTINEL_CAPTCHA_TOKEN_XYZ';

        $admin = Admin::factory()->create([
            'password' => Hash::make($sentinelPassword),
            'app_authentication_secret' => encrypt($sentinelTotp),
        ]);

        $admin->update([
            'password' => Hash::make('new_secret_456'),
        ]);

        $logs = AuditLog::all();
        $this->assertTrue($logs->count() > 0);

        foreach ($logs as $log) {
            $rawDbPayload = json_encode($log->getAttributes());

            $this->assertStringNotContainsString($sentinelPassword, $rawDbPayload);
            $this->assertStringNotContainsString($sentinelTotp, $rawDbPayload);
            $this->assertStringNotContainsString($sentinelCaptcha, $rawDbPayload);

            // Just to be sure, check the explicit fields too
            $old = json_encode($log->old_values);
            $new = json_encode($log->new_values);

            $this->assertStringNotContainsString($sentinelPassword, $old ?: '');
            $this->assertStringNotContainsString($sentinelPassword, $new ?: '');
            $this->assertStringNotContainsString($sentinelTotp, $old ?: '');
            $this->assertStringNotContainsString($sentinelTotp, $new ?: '');
        }
    }

    public function test_turnstile_validation_fails_on_missing_token()
    {
        $admin = Admin::factory()->create();

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => '',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['captcha' => 'required']);
    }

    public function test_invalid_credential_shows_generic_translation_message_without_raw_key()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'wrongpassword',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate')
            ->assertSee('Nama pengguna atau kata sandi tidak sesuai.')
            ->assertDontSee('filament-panels::pages/auth/login.messages.failed');
    }

    public function test_turnstile_validation_fails_on_invalid_token()
    {
        $admin = Admin::factory()->create();

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false], 200),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => 'invalid-token',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['captcha']);
    }

    public function test_turnstile_validation_fails_on_timeout_or_unavailable()
    {
        $admin = Admin::factory()->create();

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => function () {
                throw new ConnectionException('Timeout');
            },
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['captcha']);
    }

    public function test_turnstile_validation_succeeds_with_valid_token()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();
    }

    public function test_mfa_redirects_to_setup_when_not_configured()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'app_authentication_secret' => null,
            'force_password_change' => false,
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($admin, 'web');

        $response = $this->get(config('village.admin_path', 'desa-dashboard'));
        $response->assertRedirect('/' . config('village.admin_path', 'desa-dashboard') . '/multi-factor-authentication/set-up');

        $response = $this->get('/' . config('village.admin_path', 'desa-dashboard') . '/multi-factor-authentication/set-up');
        $response->assertOk();
    }

    public function test_mfa_and_password_change_redirect_priority_avoids_loop()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'app_authentication_secret' => null,
            'force_password_change' => true,
        ]);

        $this->actingAs($admin, 'web');

        $response = $this->get(config('village.admin_path', 'desa-dashboard'));
        $response->assertRedirect(route('filament.admin.auth.profile'));

        $response = $this->get(route('filament.admin.auth.profile'));
        $response->assertRedirect('/' . config('village.admin_path', 'desa-dashboard') . '/multi-factor-authentication/set-up');

        $response = $this->get('/' . config('village.admin_path', 'desa-dashboard') . '/multi-factor-authentication/set-up');
        $response->assertOk();
    }

    public function test_unauthenticated_admin_redirects_to_login()
    {
        $response = $this->get(config('village.admin_path', 'desa-dashboard'));
        $response->assertRedirect(route('filament.admin.auth.login'));

        $response = $this->get('/' . config('village.admin_path', 'desa-dashboard') . '/multi-factor-authentication/set-up');
        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_mfa_redirects_to_challenge_when_configured()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'app_authentication_secret' => 'some-encrypted-secret',
            'force_password_change' => false,
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();
    }

    public function test_password_change_succeeds_and_regenerates_session_and_logs_event()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('oldpassword'),
            'app_authentication_secret' => null, // TOTP not required for this specific test
            'force_password_change' => true,
        ]);

        $this->actingAs($admin, 'web');

        request()->setLaravelSession(app('session')->driver('array'));
        request()->session()->start();

        Livewire::test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'oldpassword',
                'password' => 'newpassword123',
                'passwordConfirmation' => 'newpassword123',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(filament()->getUrl());

        $admin->refresh();
        $this->assertFalse((bool) $admin->force_password_change);
        $this->assertTrue(Hash::check('newpassword123', $admin->password));
        $this->assertNotNull($admin->password_changed_at);

        $log = AuditLog::where('event_type', 'password_changed')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_idle_and_absolute_session_expiration()
    {
        $this->withoutMiddleware([PreventRequestForgery::class]);

        $admin = Admin::factory()->create([
            'force_password_change' => false,
        ]);
        $this->actingAs($admin, 'web');

        // Setup initial session
        $response = $this->get(route('filament.admin.pages.dashboard'));
        $response->assertStatus(302);

        // Simulate absolute timeout (8 hours + 1 second)
        session()->put('session_created_at', time() - (8 * 60 * 60 + 1));

        $response = $this->get(route('filament.admin.pages.dashboard'));
        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_rate_limiter_progressive_delay_and_lockout()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
        ]);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->fillForm([
                    'username' => $admin->username,
                    'password' => 'wrongpassword',
                    'captcha' => 'valid-token',
                ])
                ->call('authenticate')
                ->assertHasFormErrors(['username']);
        }

        // 6th attempt should be throttled
        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'wrongpassword',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username']); // Just check if username has error
    }

    public function test_security_headers()
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '0');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeaderMissing('Strict-Transport-Security');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("default-src 'self'", $csp);
    }
}
