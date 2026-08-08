<footer role="contentinfo" class="bg-navy text-white pt-8 md:pt-12 pb-4 border-t border-white/10 mt-auto">
    <div class="w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 mb-6 md:mb-8">
            
            <!-- Col 1: Brand & Description -->
            <div class="sm:col-span-2 lg:col-span-1 lg:pr-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3 mb-4 group hover:opacity-90 transition-opacity">
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
                        <img src="{{ $logoUrl }}" alt="{{ $villageName }}" class="h-10 w-auto">
                    @else
                        <div class="w-10 h-10 rounded-full bg-white text-navy flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.71 20.29l-1.42-1.42a10.04 10.04 0 002.71-6.87c0-5.52-4.48-10-10-10S3 6.48 3 12a10.04 10.04 0 002.71 6.87l-1.42 1.42a1 1 0 001.42 1.42l1.42-1.42a9.96 9.96 0 009.74 0l1.42 1.42a1 1 0 001.42-1.42zM12 20a8 8 0 110-16 8 8 0 010 16z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6a1 1 0 00-1 1v6a1 1 0 001 1h4a1 1 0 000-2h-3V7a1 1 0 00-1-1z"/></svg>
                        </div>
                    @endif
                    <span class="font-display font-bold text-xl md:text-2xl tracking-tight">
                        {{ $villageName }}
                    </span>
                </a>
                <p class="text-white/70 leading-relaxed text-sm md:text-base">
                    {{ \App\Services\SettingsService::get('village_description', 'Portal resmi Pemerintah Desa Kertajaya. Menghadirkan transparansi, informasi, dan pelayanan bagi seluruh lapisan masyarakat.') }}
                </p>
                <div class="flex space-x-4 mt-4">
                    @php
                        $fbUrl = \App\Services\SettingsService::get('social_facebook', '#');
                        if (str_starts_with(strtolower($fbUrl), 'javascript:')) { $fbUrl = '#'; }
                        $igUrl = \App\Services\SettingsService::get('social_instagram', '#');
                        if (str_starts_with(strtolower($igUrl), 'javascript:')) { $igUrl = '#'; }
                    @endphp
                    <a href="{{ $fbUrl }}" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-teal hover:text-white transition-colors duration-300">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" /></svg>
                    </a>
                    <a href="{{ $igUrl }}" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-teal hover:text-white transition-colors duration-300">
                        <span class="sr-only">Instagram</span>
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd" /></svg>
                    </a>
                </div>
            </div>

            <!-- Col 3: Kontak -->
            <div>
                <h4 class="font-bold text-base md:text-lg mb-4 text-white font-display">Kontak</h4>
                <ul class="space-y-3 text-sm md:text-base text-white/70">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 mt-1 text-lime" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>{{ \App\Services\SettingsService::get('address_street', 'Kantor Kepala Desa Kertajaya') }}<br>{{ \App\Services\SettingsService::get('address_subdistrict', 'Kab. Sukabumi, Jawa Barat') }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 text-lime" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span>{{ \App\Services\SettingsService::get('contact_phone', '(0266) 1234567') }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0 text-lime" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span>{{ \App\Services\SettingsService::get('contact_email', 'kontak@kertajaya.desa.id') }}</span>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Jam Pelayanan -->
            <div>
                <h4 class="font-bold text-base md:text-lg mb-4 text-white font-display">Jam Pelayanan</h4>
                <ul class="space-y-2 md:space-y-3 text-sm md:text-base text-white/70">
                    @php
                        $serviceHours = \App\Services\SettingsService::get('service_hours', [
                            ['day' => 'Senin - Kamis:', 'time' => '08.00 - 15.00'],
                            ['day' => 'Jumat:', 'time' => '08.00 - 11.30'],
                            ['day' => 'Sabtu - Minggu:', 'time' => 'Tutup'],
                        ]);
                    @endphp
                    @foreach($serviceHours as $sh)
                    <li class="flex justify-between">
                        <span>{{ $sh['day'] ?? '' }}</span>
                        <span class="text-white font-medium">{{ $sh['time'] ?? '' }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

        </div>

        <div class="pt-4 border-t border-white/10 flex flex-col md:flex-row items-center justify-between gap-4 text-xs md:text-sm text-white/50">
            <p class="truncate max-w-full md:max-w-[60%]">{{ \App\Services\SettingsService::get('footer_text', '© ' . date('Y') . ' Desa Kertajaya. Hak cipta dilindungi.') }}</p>
            @php
                $link1Label = \App\Services\SettingsService::get('footer_link_1_label');
                $link1Url = \App\Services\SettingsService::get('footer_link_1_url');
                $link2Label = \App\Services\SettingsService::get('footer_link_2_label');
                $link2Url = \App\Services\SettingsService::get('footer_link_2_url');
            @endphp
            @if($link1Label || $link2Label)
            <div class="flex items-center gap-4 whitespace-nowrap shrink-0 overflow-hidden text-ellipsis">
                @if($link1Label)
                <a href="{{ $link1Url ?: '#' }}" class="hover:text-white transition-colors truncate max-w-[150px] md:max-w-[200px] block" title="{{ $link1Label }}">{{ $link1Label }}</a>
                @endif
                @if($link2Label)
                <a href="{{ $link2Url ?: '#' }}" class="hover:text-white transition-colors truncate max-w-[150px] md:max-w-[200px] block" title="{{ $link2Label }}">{{ $link2Label }}</a>
                @endif
            </div>
            @endif
        </div>
    </div>
</footer>
