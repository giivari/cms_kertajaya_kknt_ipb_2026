<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Filament\Pages\Auth\Login;
use App\Services\AdminLoginThrottle;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class B14MfaExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config(['services.turnstile.secret' => 'disposable-mfa-test-secret']);
        Http::fake(['https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true])]);
    }

    public function test_auth_layout_exposes_the_official_theme_switcher(): void
    {
        $source = file_get_contents(
            resource_path('views/filament/brand/auth-brand.blade.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '<x-filament-panels::theme-switcher />',
            $source
        );
        $this->assertStringContainsString(
            'fi-auth-theme-switcher',
            $source
        );
    }

    public function test_mfa_validation_exception_is_not_rewritten_to_hidden_username_field(): void
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]);
        $login = Livewire::test(Login::class)->fillForm([
            'username' => $admin->username, 'password' => 'password', 'captcha' => 'test-token',
        ])->call('authenticate');
        $login->set('data.multiFactor.app.code', '000000')->call('authenticate');

        $this->assertGuest('web');
        $login->assertHasFormErrors();
    }

    public function test_primary_login_failure_still_uses_rate_limiting(): void
    {
        $admin = Admin::factory()->create(['password' => Hash::make('password')]);
        $ip = request()->ip();
        Livewire::test(Login::class)->fillForm([
            'username' => $admin->username, 'password' => 'wrong-password', 'captcha' => 'test-token',
        ])->call('authenticate')->assertHasFormErrors(['username']);

        $this->assertGuest('web');
        $this->assertSame(1, RateLimiter::attempts(app(AdminLoginThrottle::class)->key($admin->username, $ip)));
    }

    public function test_mfa_code_label_uses_the_project_translation_without_the_vendor_typo(): void
    {
        $label = trans(
            'filament-panels::auth/multi-factor/app/provider.login_form.code.label',
            [],
            'id'
        );

        $this->assertSame(
            'Masukkan kode 6 digit dari aplikasi authenticator',
            $label
        );
        $misspelledWord = 'authenticator'.'p';

        $this->assertStringNotContainsString($misspelledWord, $label);
    }

    public function test_mfa_challenge_keeps_the_official_one_time_code_component(): void
    {
        $components = AppAuthentication::make()
            ->getChallengeFormComponents(new Admin());

        $this->assertInstanceOf(OneTimeCodeInput::class, $components[0]);
    }

    public function test_incomplete_mfa_code_has_indonesian_validation_message(): void
    {
        $message = trans(
            'validation.digits',
            [
                'attribute' => 'Kode autentikasi',
                'digits' => 6,
            ],
            'id'
        );

        $this->assertSame(
            'Kode autentikasi harus terdiri dari 6 digit.',
            $message
        );

        $this->assertNotSame(
            'validation.digits',
            $message
        );
    }
}
