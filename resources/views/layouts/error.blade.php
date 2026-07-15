<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Error @yield('code') - Village CMS</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="antialiased bg-gray-50 flex items-center justify-center min-h-screen">
        <div class="text-center px-4">
            <h1 class="text-6xl font-bold text-gray-900 mb-4">@yield('code')</h1>
            <p class="text-xl text-gray-600 mb-8">@yield('message')</p>
            <a href="/" class="inline-block bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-opacity-90 transition">
                Return Home
            </a>
        </div>
    </body>
</html>
