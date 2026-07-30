<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex-shrink-0 flex items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-navy flex items-center gap-2 font-display tracking-tight">
                    @php
                        $villageName = \App\Services\SettingsService::get('village_name', 'Desa Kertajaya');
                        $logoId = \App\Services\SettingsService::get('village_logo');
                        $logoUrl = null;
                        if ($logoId) {
                            try {
                                $media = \App\Models\Media::find($logoId);
                                if ($media && $media->invisible_watermark_status === 'verified') {
                                    $deriv = $media->getPublicDerivative('thumbnail');
                                    if ($deriv) $logoUrl = Storage::disk('public')->url($deriv->file_path);
                                }
                            } catch (\Exception $e) {}
                        }
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $villageName }}" class="h-8 w-auto">
                    @else
                        <!-- Leaf/Village identity icon as requested -->
                        <svg class="w-8 h-8 text-emerald" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21.71 20.29l-1.42-1.42a10.04 10.04 0 002.71-6.87c0-5.52-4.48-10-10-10S3 6.48 3 12a10.04 10.04 0 002.71 6.87l-1.42 1.42a1 1 0 001.42 1.42l1.42-1.42a9.96 9.96 0 009.74 0l1.42 1.42a1 1 0 001.42-1.42zM12 20a8 8 0 110-16 8 8 0 010 16z"/>
                            <path d="M12 6a1 1 0 00-1 1v6a1 1 0 001 1h4a1 1 0 000-2h-3V7a1 1 0 00-1-1z"/>
                        </svg>
                    @endif
                    <span>{{ $villageName }}</span>
                </a>
            </div>

            <!-- Desktop Menu -->
            <nav class="hidden md:flex space-x-8">
                @if(isset($headerMenu) && $headerMenu->items)
                    @foreach($headerMenu->items as $item)
                        @if($item->children->isNotEmpty())
                            <div class="relative group">
                                <button class="text-navy group-hover:text-teal inline-flex items-center px-1 pt-1 border-b-2 border-transparent font-medium text-sm transition-colors duration-200">
                                    {{ $item->label }}
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div class="absolute left-0 mt-2 w-48 rounded-2xl shadow-lg bg-white ring-1 ring-border ring-opacity-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                    <div class="py-2">
                                        @foreach($item->children as $child)
                                            <a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-cream hover:text-teal transition-colors duration-200">
                                                {{ $child->label }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ $item->url }}" target="{{ $item->target }}" class="text-navy hover:text-teal inline-flex items-center px-1 pt-1 border-b-2 border-transparent font-medium text-sm transition-colors duration-200">
                                {{ $item->label }}
                            </a>
                        @endif
                    @endforeach
                @else
                    <a href="{{ route('home') }}" class="text-navy hover:text-teal font-medium text-sm transition-colors duration-200">Beranda</a>
                    <a href="{{ route('public.contact.show') }}" class="text-navy hover:text-teal font-medium text-sm transition-colors duration-200">Kontak</a>
                @endif
            </nav>

            <!-- Desktop Search -->
            <div class="hidden md:flex items-center">
                <a href="{{ route('public.search') }}" class="p-2 text-navy hover:text-teal transition-colors duration-200" title="Cari" id="desktop-search-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </a>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-navy hover:bg-cream focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal" aria-expanded="false" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <span class="sr-only">Buka menu utama</span>
                    <svg class="block h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="hidden md:hidden shadow-md absolute w-full" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1 bg-white border-t border-border">
            <!-- Mobile Search -->
            <div class="px-4 py-2">
                <form action="{{ route('public.search') }}" method="GET" class="flex gap-2">
                    <input type="text" name="q" placeholder="Cari…" minlength="2" maxlength="100" class="flex-1 rounded-xl border border-border px-3 py-2 text-sm text-navy placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal/40 focus:border-teal" id="mobile-search-input">
                    <button type="submit" class="rounded-xl bg-teal text-white px-3 py-2" id="mobile-search-submit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </form>
            </div>
            @if(isset($headerMenu) && $headerMenu->items)
                @foreach($headerMenu->items as $item)
                    @if($item->children->isNotEmpty())
                        <div class="px-4 py-2">
                            <div class="font-medium text-base text-navy">{{ $item->label }}</div>
                            <div class="mt-2 space-y-1 pl-4">
                                @foreach($item->children as $child)
                                    <a href="{{ $child->url }}" target="{{ $child->target }}" class="block px-3 py-2 rounded-xl text-base font-medium text-gray-600 hover:text-teal hover:bg-cream">
                                        {{ $child->label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item->url }}" target="{{ $item->target }}" class="block px-4 py-2 text-base font-medium text-navy hover:text-teal hover:bg-cream">
                            {{ $item->label }}
                        </a>
                    @endif
                @endforeach
            @else
                <a href="{{ route('home') }}" class="block px-4 py-2 text-base font-medium text-navy hover:text-teal hover:bg-cream">Beranda</a>
                <a href="{{ route('public.contact.show') }}" class="block px-4 py-2 text-base font-medium text-navy hover:text-teal hover:bg-cream">Kontak</a>
            @endif
        </div>
    </div>
</header>
