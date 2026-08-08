<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $defaultTitle = \App\Services\SettingsService::get('meta_title', 'Desa Kertajaya');
        $defaultDesc = \App\Services\SettingsService::get('meta_description', 'Website Resmi Pemerintahan Desa');
        $villageName = \App\Services\SettingsService::get('village_name', 'Desa Kertajaya');

        $faviconId = \App\Services\SettingsService::get('favicon');
        $faviconUrl = null;
        if ($faviconId) {
            try {
                $faviconMedia = \App\Models\Media::find($faviconId);
                if ($faviconMedia && $faviconMedia->invisible_watermark_status === 'verified') {
                    $faviconDerivative = $faviconMedia->getPublicDerivative('thumbnail');
                    if ($faviconDerivative) {
                        $faviconUrl = Storage::disk('public')->url($faviconDerivative->file_path);
                    }
                }
            } catch (\Exception $e) {}
        }
    @endphp

    <title>@hasSection('title') @yield('title') | {{ $villageName }} @else {{ $defaultTitle }} @endif</title>
    <meta name="description" content="@yield('seo_description', $defaultDesc)">

    @if($faviconUrl)
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- AlpineJS for interactivity (Header transparent & mobile menu) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $isHome = $isHome ?? request()->routeIs('home');
@endphp
<body class="font-sans antialiased text-gray-900 bg-gray-50  flex flex-col min-h-screen">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-[100] focus:p-4 focus:bg-white focus:text-navy">Skip to content</a>

    @include('partials.header')

    <main id="main-content" class="flex-grow {{ $isHome ? '' : 'pt-20' }}" tabindex="-1">
        @yield('content')
    </main>

    @include('partials.footer')

    @if(isset($isPreview) && $isPreview)
        @include('public.preview.guard')
    @endif

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Global Loader Logic
            const body = document.querySelector('body');
            const loaderHtml = `
                <div id="global-loader" class="fixed inset-0 z-[99999] bg-navy/80 backdrop-blur-sm flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
                    <div class="relative w-16 h-16 mb-4">
                        <div class="absolute inset-0 rounded-full border-4 border-white/20"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-teal border-t-transparent animate-spin"></div>
                    </div>
                    <span class="text-white font-medium text-lg tracking-wide animate-pulse">Sedang memproses...</span>
                </div>
            `;
            body.insertAdjacentHTML('beforeend', loaderHtml);
            const loader = document.getElementById('global-loader');
            let loaderTimeout;
            
            function showLoader() {
                loader.classList.remove('opacity-0', 'pointer-events-none');
                loader.classList.add('opacity-100');
            }
            
            function hideLoader() {
                loader.classList.remove('opacity-100');
                loader.classList.add('opacity-0', 'pointer-events-none');
            }

            document.addEventListener('click', function(e) {
                const target = e.target.closest('a');
                if (target && target.href) {
                    const url = new URL(target.href, window.location.origin);
                    const isSameOrigin = url.origin === window.location.origin;
                    const isHashLink = target.getAttribute('href').startsWith('#');
                    const isJsLink = target.getAttribute('href').startsWith('javascript');
                    const isNewTab = target.target === '_blank';
                    const isModifierPressed = e.ctrlKey || e.metaKey || e.shiftKey;
                    
                    if (isSameOrigin && !isHashLink && !isJsLink && !isNewTab && !isModifierPressed) {
                        loaderTimeout = setTimeout(showLoader, 150); // Small delay to avoid flash
                    }
                }
            });

            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form.target || form.target !== '_blank') {
                    showLoader();
                }
            });

            window.addEventListener('pageshow', function(e) {
                clearTimeout(loaderTimeout);
                hideLoader();
            });

            // Handle broken images
            const fallbackSrc = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNlNWU3ZWIiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTZweCIgZmlsbD0iIzZiNzI4MCIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+R2FtYmFyIHRpZGFrIGRpdGVtdWthbjwvdGV4dD48L3N2Zz4=';
            
            function handleBrokenImage(img) {
                if (img.dataset.handled) return;
                img.dataset.handled = true;
                img.src = fallbackSrc;
                img.classList.add('object-contain');
                img.classList.remove('object-cover');
                img.style.backgroundColor = '#f3f4f6'; // tailwind gray-100
            }

            document.querySelectorAll('img').forEach(function(img) {
                if (img.complete) {
                    if (img.naturalWidth === 0) handleBrokenImage(img);
                } else {
                    img.addEventListener('error', function() { handleBrokenImage(this); });
                }
            });
        });
    </script>
</body>
</html>
