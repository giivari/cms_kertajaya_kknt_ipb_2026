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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
    @include('partials.header')

    <main class="flex-grow">
        @yield('content')
    </main>

    @include('partials.footer')
</body>
</html>
