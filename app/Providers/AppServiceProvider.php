<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Menu;
use App\Models\MenuItem;
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
        // Memaksa sistem menggunakan HTTPS untuk Cloudflare saat production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

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
            $menu = null;
            if (app()->has(\App\Support\Preview\PreviewContext::class)) {
                $context = app(\App\Support\Preview\PreviewContext::class);
                if ($context->previewType === 'menu' && isset($context->normalizedState['location']) && $context->normalizedState['location'] === Menu::HEADER) {
                    $attributes = array_merge($context->recordSnapshot ?? [], $context->normalizedState);
                    $itemsArray = $attributes['items'] ?? [];
                    unset($attributes['items']);

                    $menu = new Menu();
                    $menu->forceFill($attributes);
                    
                    $items = collect($itemsArray)->filter(function($item) {
                        return $item['is_visible'] ?? false;
                    })->map(function($item, $index) {
                        $childrenArray = $item['children'] ?? [];
                        unset($item['children']);
                        
                        $menuItem = new MenuItem();
                        $menuItem->forceFill($item);
                        $menuItem->position = $index;
                        
                        $children = collect($childrenArray)->filter(function($child) {
                            return $child['is_visible'] ?? false;
                        })->map(function($child, $childIndex) {
                            $childItem = new MenuItem();
                            $childItem->forceFill($child);
                            $childItem->position = $childIndex;
                            return $childItem;
                        });
                        $menuItem->setRelation('children', $children);
                        return $menuItem;
                    });

                    $menu->setRelation('items', $items);
                }
            }

            if (!$menu) {
                $menu = Menu::where('location', Menu::HEADER)->with(['items' => function ($query) {
                    $query->where('is_visible', true)->whereNull('parent_id')->orderBy('position');
                }, 'items.children' => function ($query) {
                    $query->where('is_visible', true)->orderBy('position');
                }, 'items.page', 'items.children.page'])->first();
            }
            $view->with('headerMenu', $menu);
        });

        View::composer('partials.footer', function ($view) {
            $menu = null;
            if (app()->has(\App\Support\Preview\PreviewContext::class)) {
                $context = app(\App\Support\Preview\PreviewContext::class);
                if ($context->previewType === 'menu' && isset($context->normalizedState['location']) && $context->normalizedState['location'] === Menu::HEADER) {
                    $attributes = array_merge($context->recordSnapshot ?? [], $context->normalizedState);
                    $itemsArray = $attributes['items'] ?? [];
                    unset($attributes['items']);

                    $menu = new Menu();
                    $menu->forceFill($attributes);
                    
                    $items = collect($itemsArray)->filter(function($item) {
                        return $item['is_visible'] ?? false;
                    })->map(function($item, $index) {
                        $childrenArray = $item['children'] ?? [];
                        unset($item['children']);
                        
                        $menuItem = new MenuItem();
                        $menuItem->forceFill($item);
                        $menuItem->position = $index;
                        
                        $children = collect($childrenArray)->filter(function($child) {
                            return $child['is_visible'] ?? false;
                        })->map(function($child, $childIndex) {
                            $childItem = new MenuItem();
                            $childItem->forceFill($child);
                            $childItem->position = $childIndex;
                            return $childItem;
                        });
                        $menuItem->setRelation('children', $children);
                        return $menuItem;
                    });

                    $menu->setRelation('items', $items);
                }
            }

            if (!$menu) {
                $menu = Menu::where('location', Menu::HEADER)->with(['items' => function ($query) {
                    $query->where('is_visible', true)->whereNull('parent_id')->orderBy('position');
                }, 'items.page'])->first();
            }
            $view->with('footerMenu', $menu);
        });
    }
}