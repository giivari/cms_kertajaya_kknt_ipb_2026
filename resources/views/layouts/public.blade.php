<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Village CMS')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gray-50 text-gray-900 font-sans">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <a href="/" class="text-xl font-bold text-primary">Village CMS</a>
                <nav class="hidden md:flex space-x-4">
                    <a href="/" class="text-gray-600 hover:text-gray-900">Home</a>
                </nav>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>

        <footer class="bg-gray-800 text-white mt-12 py-8 text-center">
            <p>&copy; {{ date('Y') }} Village CMS. All rights reserved.</p>
        </footer>
    </body>
</html>
