<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Menu;
use App\Services\DocumentMediaUsageResolver;
use App\Services\GalleryMediaUsageResolver;
use App\Services\MediaUsageService;
use App\Services\NewsMediaUsageResolver;
use App\Services\PageMediaUsageResolver;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, Admin::class);
        $this->app->bind(\Filament\Actions\Exports\Jobs\ExportCompletion::class, \App\Jobs\Exports\CompleteAdminExport::class);
        $this->app->bind(\Filament\Actions\Exports\Jobs\CreateXlsxFile::class, \App\Jobs\Exports\CreateAdminXlsxFile::class);
        $this->app->bind(\Filament\Actions\Exports\Downloaders\CsvDownloader::class, \App\Support\Exports\ControlledCsvDownloader::class);
        $this->app->bind(\Filament\Actions\Exports\Downloaders\XlsxDownloader::class, \App\Support\Exports\ControlledXlsxDownloader::class);

        $this->app->singleton(MediaUsageService::class, function ($app) {
            return new MediaUsageService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event): void {
            if ($event->guard === 'web' && $event->user instanceof Admin) {
                \App\Services\AuditLogService::log('admin_login', $event->user);
            }
        });
        Event::listen(\Illuminate\Auth\Events\Logout::class, function (\Illuminate\Auth\Events\Logout $event): void {
            if ($event->guard === 'web' && $event->user instanceof Admin) {
                \App\Services\AuditLogService::log('admin_logout', $event->user);
            }
        });
        Event::listen(\Illuminate\Auth\Events\Failed::class, function (\Illuminate\Auth\Events\Failed $event): void {
            if ($event->guard === 'web') {
                // The Failed event also carries submitted credentials. Never
                // copy them, or a guessed account name, into the audit row.
                \App\Services\AuditLogService::log('admin_login_failed');
            }
        });

        // Memaksa sistem menggunakan HTTPS untuk Cloudflare saat production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        RateLimiter::for('contact-submissions', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes(15, 5)->by($request->ip());
        });
        
        $this->app->booted(function () {
            // Extend vendor routes while preserving their controller owner checks.
            $this->app['router']->pushMiddlewareToGroup('filament.actions', 'admin.security');
            $mediaUsageService = $this->app->make(MediaUsageService::class);
            $mediaUsageService->registerResolver(new PageMediaUsageResolver);
            $mediaUsageService->registerResolver(new NewsMediaUsageResolver);
            $mediaUsageService->registerResolver(new GalleryMediaUsageResolver);
            $mediaUsageService->registerResolver(new DocumentMediaUsageResolver);
            $mediaUsageService->registerResolver(new \App\Services\SettingsAndLocationMediaUsageResolver);
        });

        View::composer('partials.header', function ($view) {
            $context = app()->bound(\App\Support\Preview\PreviewContext::class)
                ? app(\App\Support\Preview\PreviewContext::class) : null;
            $view->with('headerMenu', app(\App\Services\NavigationResolver::class)->forLocation(
                Menu::HEADER,
                $context?->previewType === 'menu' ? $context->normalizedState : null,
                $context?->recordSnapshot,
            ));
        });
    }
}
