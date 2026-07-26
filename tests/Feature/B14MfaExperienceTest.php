<?php

namespace Tests\Feature;

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

    public function test_auth_card_remains_inset_on_mobile(): void
    {
        $source = file_get_contents(
            resource_path('css/filament/admin/theme.css')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'B1.4 mobile auth card inset',
            $source
        );
        $this->assertStringContainsString(
            '.fi-simple-layout .fi-simple-main',
            $source
        );
        $this->assertStringContainsString(
            'width: calc(100% - 2rem);',
            $source
        );
        $this->assertStringContainsString(
            'margin-inline: 1rem;',
            $source
        );
        $this->assertStringContainsString(
            'border-radius: 0.75rem;',
            $source
        );
    }
    public function test_mfa_code_inputs_fill_the_auth_card_width(): void
    {
        $source = file_get_contents(
            resource_path('css/filament/admin/theme.css')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'B1.4 full-width auth / MFA content',
            $source
        );
        $this->assertStringContainsString(
            '.fi-simple-main .fi-one-time-code-input-ctn',
            $source
        );
        $this->assertStringContainsString(
            'width: 100%;',
            $source
        );
        $this->assertStringContainsString(
            'margin-inline: 0;',
            $source
        );
        $this->assertStringContainsString(
            'justify-content: space-between;',
            $source
        );
        $this->assertStringContainsString(
            'flex: 1 1 0;',
            $source
        );
    }
    public function test_auth_vertical_rhythm_is_responsive(): void
    {
        $brandSource = file_get_contents(
            resource_path('views/filament/brand/auth-brand.blade.php')
        );

        $themeSource = file_get_contents(
            resource_path('css/filament/admin/theme.css')
        );

        $this->assertIsString($brandSource);
        $this->assertIsString($themeSource);

        $this->assertStringContainsString(
            'admin-auth-brand',
            $brandSource
        );

        $this->assertStringContainsString(
            'B1.4 responsive auth vertical rhythm',
            $themeSource
        );

        $this->assertStringContainsString(
            'transform: translateY(2rem);',
            $themeSource
        );

        $this->assertStringContainsString(
            'transform: translateY(2.5rem);',
            $themeSource
        );

        $this->assertStringContainsString(
            'margin-top: -4rem;',
            $themeSource
        );
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
    }}