<?php

namespace App\Providers;

use App\Services\MediaUsageService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MediaUsageService::class, function ($app) {
            return new MediaUsageService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $mediaUsageService = $this->app->make(\App\Services\MediaUsageService::class);
            $mediaUsageService->registerResolver(new \App\Services\PageMediaUsageResolver());
        });

        \Illuminate\Support\Facades\View::composer('partials.header', function ($view) {
            $view->with('headerMenu', \App\Models\Menu::where('location', 'header_menu')->with(['items' => function ($query) {
                $query->where('is_visible', true)->whereNull('parent_id')->orderBy('position');
            }, 'items.children' => function ($query) {
                $query->where('is_visible', true)->orderBy('position');
            }, 'items.page', 'items.children.page'])->first());
        });

        \Illuminate\Support\Facades\View::composer('partials.footer', function ($view) {
            $view->with('footerMenu', \App\Models\Menu::where('location', 'footer_menu')->with(['items' => function ($query) {
                $query->where('is_visible', true)->whereNull('parent_id')->orderBy('position');
            }, 'items.page'])->first());
        });
    }
}
