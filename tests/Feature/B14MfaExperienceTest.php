<?php

namespace Tests\Feature;

use App\Models\Admin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Forms\Components\OneTimeCodeInput;
use Tests\TestCase;

class B14MfaExperienceTest extends TestCase
{
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
        $source = file_get_contents(
            app_path('Filament/Pages/Auth/Login.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'if (filled($this->userUndertakingMultiFactorAuthentication))',
            $source
        );
        $this->assertStringContainsString(
            'throw $e;',
            $source
        );
    }

    public function test_primary_login_failure_still_uses_rate_limiting(): void
    {
        $source = file_get_contents(
            app_path('Filament/Pages/Auth/Login.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'RateLimiter::hit($key, 900);',
            $source
        );
        $this->assertStringContainsString(
            "'data.username' => __('filament-panels::pages/auth/login.messages.failed')",
            $source
        );
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
