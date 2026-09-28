<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\WebsiteSettings as WebsiteSettingsPage;
use App\Models\Admin;
use App\Models\AuditLog;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config(['services.turnstile.secret' => 'disposable-test-secret']);
    }

    public function test_invalid_totp_denies_access()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true])]);
        $login = Livewire::test(Login::class)->fillForm([
            'username' => $admin->username, 'password' => 'password', 'captcha' => 'test-token',
        ])->call('authenticate');

        $this->assertGuest('web');
        $login->set('data.multiFactor.app.code', '000000')->call('authenticate');
        $this->assertGuest('web');
    }

    public function test_valid_totp_permits_dashboard_access()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true])]);
        $login = Livewire::test(Login::class)->fillForm([
            'username' => $admin->username, 'password' => 'password', 'captcha' => 'test-token',
        ])->call('authenticate');

        $this->assertGuest('web');
        $login->set('data.multiFactor.app.code', AppAuthentication::make()->getCurrentCode($admin))
            ->call('authenticate');
        $this->assertAuthenticatedAs($admin, 'web');
        $this->get(route('filament.admin.pages.dashboard'))->assertOk();
    }

    public function test_recovery_codes_are_unavailable()
    {
        // Asserting that the app_authentication_recovery_codes column does not exist
        $this->assertFalse(Schema::hasColumn('admins', 'app_authentication_recovery_codes'));
    }

    public function test_totp_secret_is_encrypted_at_rest()
    {
        $rawSecret = 'JBSWY3DPEHPK3PXP';
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'app_authentication_secret' => $rawSecret,
        ]);

        $rawDbAdmin = DB::table('admins')->where('id', $admin->id)->first();
        $this->assertNotEquals($rawSecret, $rawDbAdmin->app_authentication_secret);
        $this->assertStringContainsString('eyJ', $rawDbAdmin->app_authentication_secret); // Laravel encrypted payload signature
    }

    public function test_password_change_fails_with_incorrect_current_password()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $this->actingAs($admin, 'web');

        Livewire::test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'wrongpassword',
                'password' => 'Newpassword123',
                'passwordConfirmation' => 'Newpassword123',
            ])
            ->call('save')
            ->assertHasFormErrors();
    }

    public function test_password_change_fails_with_incorrect_totp()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('oldpassword'),
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        $this->actingAs($admin, 'web');

        Livewire::test(EditProfile::class)
            ->fillForm([
                'currentPassword' => 'oldpassword',
                'password' => 'Newpassword123',
                'passwordConfirmation' => 'Newpassword123',
                'totp' => '000000',
            ])
            ->call('save')
            ->assertHasFormErrors();
    }

    public function test_expired_absolute_session_denies_dashboard_request()
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin, 'web')->withSession(['session_created_at' => time() - 8 * 60 * 60 - 1]);
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest('web');
    }

    public function test_logout_invalidates_session()
    {
        $admin = Admin::factory()->create(['app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin, 'web')->withSession(['session_created_at' => time()]);

        $this->post(route('filament.admin.auth.logout'))
            ->assertRedirect();

        $this->assertGuest('web');
    }

    public function test_csrf_token_is_regenerated_on_login()
    {
        $admin = Admin::factory()->create(['password' => Hash::make('password')]);
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true])]);
        session()->start();
        $oldSessionId = session()->getId();
        Livewire::test(Login::class)->fillForm([
            'username' => $admin->username, 'password' => 'password', 'captcha' => 'test-token',
        ])->call('authenticate');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertNotSame($oldSessionId, session()->getId());
    }

    public function test_secure_session_cookie_configuration_is_respected()
    {
        Config::set('session.secure', true);
        $response = $this->get('/');
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
    }

    public function test_login_rate_limiter_records_a_failed_attempt_for_the_component_and_ip()
    {
        $admin = Admin::factory()->create(['password' => Hash::make('password')]);
        Http::fake(['*' => Http::response(['success' => true], 200)]);

        $username = $admin->username;
        $ip = request()->ip();

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $username,
                'password' => 'wrongpassword',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate');

        $key = app(\App\Services\AdminLoginThrottle::class)->key($admin->username, $ip);
        $this->assertSame(1, RateLimiter::attempts($key));
    }

    public function test_successful_authentication_after_a_failed_attempt_still_requires_valid_credentials()
    {
        $this->freezeTime();
        $admin = Admin::factory()->create(['password' => Hash::make('password')]);
        Http::fake(['*' => Http::response(['success' => true], 200)]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'wrongpassword',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate');

        $this->assertGuest('web');
        $this->travel(1)->seconds();

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => 'valid-token',
            ])
            ->call('authenticate');

        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_hsts_header_in_production()
    {
        $originalEnvironment = app()->environment();
        try {
            app()->detectEnvironment(fn () => 'production');
            $this->assertTrue(app()->environment('production'));
            $this->get('https://localhost/')->assertHeader(
                'Strict-Transport-Security', 'max-age=31536000',
            );
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }
    }

    public function test_website_settings_validation()
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'web');

        Livewire::test(WebsiteSettingsPage::class)
            ->fillForm([
                'village_name' => '', // Required
            ])
            ->call('save')
            ->assertHasFormErrors(['village_name' => 'required']);
    }

    public function test_website_settings_audit_logs_redact_sensitive_information()
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $admin = Admin::factory()->create(['app_authentication_secret' => $secret]);
        $this->actingAs($admin, 'web');

        Livewire::test(WebsiteSettingsPage::class)
            ->fillForm([
                'village_name' => 'Test Village',
            ])
            ->call('save');

        $logs = AuditLog::query()->where('subject_type', \App\Models\WebsiteSetting::class)->get();
        $this->assertNotEmpty($logs);
        $this->assertStringNotContainsString($secret, $logs->toJson());
        $this->assertStringNotContainsString($admin->password, $logs->toJson());
    }
}
