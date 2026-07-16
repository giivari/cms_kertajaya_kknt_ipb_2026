<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex-shrink-0 flex items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-emerald-600 flex items-center gap-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>{{ config('app.name', 'Desa') }}</span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <nav class="hidden md:flex space-x-8">
                @if(isset($headerMenu) && $headerMenu->items)
                    @foreach($headerMenu->items as $item)
                        @if($item->children->isNotEmpty())
                            <div class="relative group">
                                <button class="text-gray-600 group-hover:text-emerald-600 inline-flex items-center px-1 pt-1 border-b-2 border-transparent font-medium text-sm transition-colors duration-200">
                                    {{ $item->label }}
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div class="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                    <div class="py-1">
                                        @foreach($item->children as $child)
                                            <a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-emerald-600 transition-colors duration-200">
                                                {{ $child->label }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ $item->url }}" target="{{ $item->target }}" class="text-gray-600 hover:text-emerald-600 inline-flex items-center px-1 pt-1 border-b-2 border-transparent font-medium text-sm transition-colors duration-200">
                                {{ $item->label }}
                            </a>
                        @endif
                    @endforeach
                @else
                    <a href="{{ route('home') }}" class="text-gray-600 hover:text-emerald-600 font-medium text-sm transition-colors duration-200">Beranda</a>
                @endif
            </nav>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500" aria-expanded="false" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <span class="sr-only">Buka menu utama</span>
                    <svg class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="hidden md:hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1 bg-white border-t border-gray-200">
            @if(isset($headerMenu) && $headerMenu->items)
                @foreach($headerMenu->items as $item)
                    @if($item->children->isNotEmpty())
                        <div class="px-4 py-2">
                            <div class="font-medium text-base text-gray-800">{{ $item->label }}</div>
                            <div class="mt-2 space-y-1 pl-4">
                                @foreach($item->children as $child)
                                    <a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-50">
                                        {{ $child->label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->url }}" target="{{ $item->target }}" class="block px-4 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                            {{ $item->label }}
                        </a>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</header>
