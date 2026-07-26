<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use Tests\TestCase;

class B13LoginVisualTest extends TestCase
{
    public function test_auth_brand_view_contains_expected_visual_copy(): void
    {
        $source = file_get_contents(
            resource_path('views/filament/brand/auth-brand.blade.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'CMS Desa Kertajaya',
            $source
        );
        $this->assertStringContainsString(
            'Sistem Pengelolaan Website Desa',
            $source
        );
        $this->assertStringContainsString(
            'heroicon-s-shield-check',
            $source
        );
        $this->assertStringContainsString(
            'dark:text-white',
            $source
        );
    }

    public function test_auth_brand_hook_is_scoped_to_login_page(): void
    {
        $source = file_get_contents(
            app_path('Providers/Filament/AdminPanelProvider.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'PanelsRenderHook::SIMPLE_LAYOUT_START',
            $source
        );
        $this->assertStringContainsString(
            "view('filament.brand.auth-brand')",
            $source
        );
        $this->assertStringContainsString(
            'scopes: [\App\Filament\Pages\Auth\Login::class]',
            $source
        );
    }

    public function test_login_page_uses_custom_copy_and_disables_default_logo(): void
    {
        $loginPage = new Login();

        $this->assertFalse($loginPage->hasLogo());
        $this->assertSame(
            'Masuk ke Panel Admin',
            (string) $loginPage->getHeading()
        );
        $this->assertSame(
            '',
            (string) $loginPage->getSubheading()
        );
    }

    public function test_login_action_label_and_turnstile_are_preserved(): void
    {
        $source = file_get_contents(
            app_path('Filament/Pages/Auth/Login.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "->label('Masuk')",
            $source
        );
        $this->assertStringContainsString(
            'Turnstile',
            $source
        );
        $this->assertStringContainsString(
            "'captcha'",
            $source
        );
    }

    public function test_mfa_copy_does_not_depend_on_missing_translation_keys(): void
    {
        $source = file_get_contents(
            app_path('Filament/Pages/Auth/Login.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'Verifikasi Dua Faktor'",
            $source
        );
        $this->assertStringContainsString(
            "'Konfirmasi identitas Anda untuk melanjutkan.'",
            $source
        );
        $this->assertStringNotContainsString(
            'filament-panels::pages/auth/login.multi_factor',
            $source
        );
    }}