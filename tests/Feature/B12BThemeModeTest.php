<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Filament\Enums\ThemeMode;
use Tests\TestCase;

class B12BThemeModeTest extends TestCase
{
    public function test_admin_panel_has_dark_mode_enabled(): void
    {
        $this->assertTrue(Filament::hasDarkMode(), 'Dark mode must be enabled.');
    }

    public function test_admin_panel_default_theme_is_dark(): void
    {
        $this->assertEquals(ThemeMode::Dark, Filament::getDefaultThemeMode(), 'Default theme must be Dark.');
    }

    public function test_admin_panel_preserves_sidebar_configuration(): void
    {
        $this->assertEquals('16rem', Filament::getSidebarWidth(), 'Sidebar width must be 16rem.');
    }

    public function test_admin_panel_preserves_lihat_website_hook(): void
    {
        $providerContent = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));

        $this->assertStringContainsString(
            'PanelsRenderHook::GLOBAL_SEARCH_AFTER',
            $providerContent,
            'Lihat Website must remain between global search and the user menu.'
        );

        $this->assertStringContainsString(
            "view('filament.topbar-website-link')",
            $providerContent,
            'Lihat Website topbar hook must be preserved.'
        );

        $this->assertStringNotContainsString(
            'PanelsRenderHook::GLOBAL_SEARCH_BEFORE',
            $providerContent,
            'Lihat Website must not move before the global search field.'
        );
    }
}
