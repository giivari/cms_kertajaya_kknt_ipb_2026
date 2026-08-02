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

        $this->app->singleton(MediaUsageService::class, function ($app) {
            return new MediaUsageService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('contact-submissions', function (Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes(15, 5)->by($request->ip());
        });
        $this->app->booted(function () {
            $mediaUsageService = $this->app->make(MediaUsageService::class);
            $mediaUsageService->registerResolver(new PageMediaUsageResolver);
            $mediaUsageService->registerResolver(new NewsMediaUsageResolver);
            $mediaUsageService->registerResolver(new GalleryMediaUsageResolver);
            $mediaUsageService->registerResolver(new DocumentMediaUsageResolver);
        });

        View::composer('partials.header', function ($view) {
            $view->with('headerMenu', Menu::where('location', Menu::HEADER)->with(['items' => function ($query) {
                $query->where('is_visible', true)->whereNull('parent_id')->orderBy('position');
            }, 'items.children' => function ($query) {
                $query->where('is_visible', true)->orderBy('position');
            }, 'items.page', 'items.children.page'])->first());
        });

        View::composer('partials.footer', function ($view) {
            $view->with('footerMenu', Menu::where('location', Menu::FOOTER)->with(['items' => function ($query) {
                $query->where('is_visible', true)->whereNull('parent_id')->orderBy('position');
            }, 'items.page'])->first());
        });
    }
}
