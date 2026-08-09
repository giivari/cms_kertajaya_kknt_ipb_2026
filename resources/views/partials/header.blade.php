@php
    $villageName = \App\Services\SettingsService::get('village_name', 'Desa Kertajaya');
    $logoId = \App\Services\SettingsService::get('village_logo');
    $logoUrl = null;
    if ($logoId) {
        try {
            $media = \App\Models\Media::find($logoId);
            if ($media && $media->invisible_watermark_status?->value === 'verified') {
                $deriv = $media->getPublicDerivative('thumbnail');
                if ($deriv) $logoUrl = Storage::disk('public')->url($deriv->filename);
            }
        } catch (\Exception $e) {}
    }
    
    // For transparent header effect on home page
    $isHome = $isHome ?? request()->routeIs('home');
@endphp

<header 
    x-data="{ 
        isScrolled: false, 
        isMobileMenuOpen: false,
        isHome: {{ $isHome ? 'true' : 'false' }},
        get isTransparent() {
            return this.isHome && !this.isScrolled;
        }
    }"
    @scroll.window="isScrolled = (window.pageYOffset > 20)"
    :class="isTransparent ? 'bg-transparent py-6' : 'bg-white shadow-sm py-4'"
    class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
>
    <div class="w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $villageName }}" class="h-10 w-auto">
                    @else
                        <!-- Village Logo Placeholder -->
                        <div 
                            :class="isTransparent ? 'bg-white text-navy' : 'bg-navy text-white'"
                            class="w-10 h-10 rounded-full flex items-center justify-center transition-colors duration-300 shadow-sm group-hover:scale-105"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.71 20.29l-1.42-1.42a10.04 10.04 0 002.71-6.87c0-5.52-4.48-10-10-10S3 6.48 3 12a10.04 10.04 0 002.71 6.87l-1.42 1.42a1 1 0 001.42 1.42l1.42-1.42a9.96 9.96 0 009.74 0l1.42 1.42a1 1 0 001.42-1.42zM12 20a8 8 0 110-16 8 8 0 010 16z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6a1 1 0 00-1 1v6a1 1 0 001 1h4a1 1 0 000-2h-3V7a1 1 0 00-1-1z"/></svg>
                        </div>
                    @endif
                    <span 
                        :class="isTransparent ? 'text-white drop-shadow-md' : 'text-navy'"
                        class="font-display font-bold text-lg md:text-xl xl:text-2xl whitespace-nowrap transition-colors duration-300"
                    >
                        {{ $villageName }}
                    </span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <nav aria-label="Navigasi Utama" class="hidden lg:flex items-center gap-4 xl:gap-8">
                @if(isset($headerMenu) && $headerMenu->items->isNotEmpty())
                    @foreach($headerMenu->items as $item)
                        @if($item->children->isNotEmpty())
                            <div class="relative group" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                <button 
                                    :class="isTransparent ? 'text-white drop-shadow-sm hover:opacity-80' : 'text-navy hover:text-teal'"
                                    class="inline-flex items-center text-sm font-medium whitespace-nowrap transition-colors duration-200"
                                >
                                    {{ $item->label }}
                                    <svg class="ml-1 h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div 
                                    x-show="open" 
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-2"
                                    class="absolute left-0 mt-2 w-48 rounded-2xl shadow-xl bg-white ring-1 ring-border ring-opacity-100 z-50 py-2"
                                    style="display: none;"
                                >
                                    @foreach($item->children as $child)
                                        <a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-cream hover:text-teal transition-colors duration-200">
                                            {{ $child->label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a 
                                href="{{ $item->url }}" 
                                target="{{ $item->target }}" 
                                :class="isTransparent ? 'text-white drop-shadow-sm hover:opacity-80' : 'text-navy hover:text-teal'"
                                class="text-sm font-medium whitespace-nowrap transition-colors duration-200"
                            >
                                {{ $item->label }}
                            </a>
                        @endif
                    @endforeach
                @else
                    <a href="{{ route('home') }}" :class="isTransparent ? 'text-white drop-shadow-sm hover:opacity-80' : 'text-navy hover:text-teal'" class="text-sm font-medium transition-colors duration-200">Beranda</a>
                    <a href="{{ route('public.map.index') }}" :class="isTransparent ? 'text-white drop-shadow-sm hover:opacity-80' : 'text-navy hover:text-teal'" class="text-sm font-medium transition-colors duration-200">Peta</a>
                    <a href="{{ route('public.contact.show') }}" :class="isTransparent ? 'text-white drop-shadow-sm hover:opacity-80' : 'text-navy hover:text-teal'" class="text-sm font-medium transition-colors duration-200">Kontak</a>
                @endif
            </nav>

            <div class="flex items-center gap-2 lg:gap-4">
                <a href="{{ route('public.search') }}" 
                    aria-label="Cari Informasi"
                    :class="isTransparent ? 'text-white hover:bg-white/20 focus:ring-white' : 'text-navy hover:bg-black/5 focus:ring-teal'"
                    class="p-2 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2" title="Cari"
                >
                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </a>
                
                <a href="{{ route('public.contact.show') }}" 
                    :class="isTransparent ? 'bg-white text-navy hover:bg-white/90' : 'bg-teal text-white hover:bg-teal/90'"
                    class="hidden md:inline-flex items-center justify-center rounded-full font-medium whitespace-nowrap transition-colors shadow-sm px-4 xl:px-6 py-2 xl:py-2.5 text-sm"
                >
                    Informasi Publik
                </a>
                
                <button 
                    @click="isMobileMenuOpen = true"
                    aria-label="Buka Menu Navigasi"
                    :aria-expanded="isMobileMenuOpen"
                    :class="isTransparent ? 'text-white hover:bg-white/20 focus:ring-white' : 'text-navy hover:bg-black/5 focus:ring-teal'"
                    class="lg:hidden p-2 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2"
                >
                    <svg class="w-6 h-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div x-show="isMobileMenuOpen" class="fixed inset-0 z-50 lg:hidden" style="display: none;">
        <!-- Backdrop -->
        <div 
            x-show="isMobileMenuOpen" 
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm"
            @click="isMobileMenuOpen = false"
        ></div>

        <!-- Slide-over panel -->
        <div 
            x-show="isMobileMenuOpen" 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-[85vw] max-w-sm bg-white shadow-2xl flex flex-col"
        >
            <div class="flex items-center justify-between p-6 border-b border-border">
                <span class="font-display font-bold text-xl text-navy" id="mobile-menu-title">Menu</span>
                <button @click="isMobileMenuOpen = false" aria-label="Tutup Menu Navigasi" class="p-2 -mr-2 text-gray-500 hover:text-navy hover:bg-gray-100 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-teal">
                    <svg class="w-6 h-6" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-2">
                <!-- Mobile Search -->
                <form action="{{ route('public.search') }}" method="GET" role="search" class="flex gap-2 mb-6">
                    <input type="text" name="q" placeholder="Cari informasi..." aria-label="Kata kunci pencarian" required class="flex-1 rounded-full border border-border bg-gray-50 px-4 py-2.5 text-sm text-navy focus:outline-none focus:ring-2 focus:ring-teal/40 focus:border-teal">
                    <button type="submit" aria-label="Mulai Pencarian" class="rounded-full bg-teal text-white w-10 flex items-center justify-center shrink-0 shadow-sm focus:outline-none focus:ring-2 focus:ring-teal/40">
                        <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </form>

                @if(isset($headerMenu) && $headerMenu->items->isNotEmpty())
                    @foreach($headerMenu->items as $item)
                        @if($item->children->isNotEmpty())
                            <div x-data="{ expanded: false }" class="border-b border-gray-100 pb-2 mb-2">
                                <button @click="expanded = !expanded" class="w-full flex items-center justify-between text-lg font-medium text-navy hover:text-teal py-2 transition-colors">
                                    {{ $item->label }}
                                    <svg class="w-5 h-5 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="expanded" x-collapse class="pl-4 mt-2 space-y-2">
                                    @foreach($item->children as $child)
                                        <a href="{{ $child->url }}" target="{{ $child->target }}" class="block py-2 text-base text-gray-600 hover:text-teal transition-colors">
                                            {{ $child->label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item->url }}" target="{{ $item->target }}" class="block py-3 border-b border-gray-100 text-lg font-medium text-navy hover:text-teal transition-colors">
                                {{ $item->label }}
                            </a>
                        @endif
                    @endforeach
                @else
                    <a href="{{ route('home') }}" class="block py-3 border-b border-gray-100 text-lg font-medium text-navy hover:text-teal transition-colors">Beranda</a>
                    <a href="{{ route('public.map.index') }}" class="block py-3 border-b border-gray-100 text-lg font-medium text-navy hover:text-teal transition-colors">Peta</a>
                    <a href="{{ route('public.contact.show') }}" class="block py-3 border-b border-gray-100 text-lg font-medium text-navy hover:text-teal transition-colors">Kontak</a>
                @endif
            </div>

            <div class="p-6 border-t border-border mt-auto">
                <a href="{{ route('public.contact.show') }}" class="flex w-full items-center justify-center rounded-full bg-teal px-6 py-3 text-base font-medium text-white hover:bg-teal/90 shadow-sm transition-colors">
                    Informasi Publik
                </a>
            </div>
        </div>
    </div>
</header>
