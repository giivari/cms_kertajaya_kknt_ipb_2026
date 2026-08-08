<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\LocalInitialsAvatarProvider;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\MyProfile;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\AbsoluteSessionTimeout;
use App\Http\Middleware\ForcePasswordChange;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Navigation\MenuItem;
use Filament\View\PanelsIconAlias;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('village.admin_path', 'desa-dashboard'))
            ->spa()
            ->login(Login::class)
            ->profile(EditProfile::class)
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => 'Profil')
                    ->url(fn (): string => MyProfile::getUrl())
                    ->icon('heroicon-o-user'),
            ])
            ->multiFactorAuthentication(
                providers: [
                    AppAuthentication::make(),
                ],
                isRequired: true
            )
            ->brandLogo(fn () => view('filament.brand.sidebar-logo'))
            ->brandLogoHeight('2.5rem')
            ->defaultAvatarProvider(LocalInitialsAvatarProvider::class)
            ->sidebarWidth('16rem')
            ->favicon(fn () => (function() {
                $faviconId = \App\Services\SettingsService::get('favicon');
                if ($faviconId) {
                    try {
                        $faviconMedia = \App\Models\Media::find($faviconId);
                        if ($faviconMedia && $faviconMedia->invisible_watermark_status?->value === 'verified') {
                            $faviconDerivative = $faviconMedia->getPublicDerivative('thumbnail');
                            if ($faviconDerivative) {
                                return \Illuminate\Support\Facades\Storage::disk('public')->url($faviconDerivative->filename);
                            }
                        }
                    } catch (\Exception $e) {}
                }
                return null;
            })())
            ->icons([
                PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => Heroicon::OutlinedBars3,
                PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => Heroicon::OutlinedBars3,
                PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => Heroicon::OutlinedBars3,
                PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => Heroicon::OutlinedBars3,
            ])
            ->colors([
                'primary' => Color::Teal,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->defaultThemeMode(ThemeMode::Dark)
            ->databaseNotifications()
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.styles')
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => view('filament.topbar-website-link')
            )
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn () => view('filament.brand.auth-brand'),
                scopes: [\App\Filament\Pages\Auth\Login::class]
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.global-loader')
            )
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('Kelola Konten')->collapsible(false),
                \Filament\Navigation\NavigationGroup::make('Kelola Website')->collapsible(false),
                \Filament\Navigation\NavigationGroup::make('Komunikasi')->collapsible(false),
                \Filament\Navigation\NavigationGroup::make('Lainnya')->collapsible(false),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ForcePasswordChange::class,
                AbsoluteSessionTimeout::class,
            ]);
    }
}
