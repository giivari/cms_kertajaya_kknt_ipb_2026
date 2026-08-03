@extends('layouts.public')
@section('title', 'Beranda')

@section('content')

@php
    $getMediaUrl = function($mediaId, $derivative = 'large', $fallback = '') {
        if (!$mediaId) return $fallback;
        try {
            $media = \App\Models\Media::find($mediaId);
            if ($media && $media->invisible_watermark_status === 'verified') {
                $deriv = $media->getPublicDerivative($derivative);
                if ($deriv) return Storage::disk('public')->url($deriv->file_path);
            }
        } catch (\Exception $e) {}
        return $fallback;
    };
@endphp

<!-- Hero Section -->
<section class="relative min-h-[90vh] md:min-h-screen flex flex-col justify-center overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img 
            src="{{ $getMediaUrl(\App\Services\SettingsService::get('hero_image'), 'large', 'https://images.unsplash.com/photo-1644496580843-07a889de9072?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwyfHxpbmRvbmVzaWElMjB2aWxsYWdlJTIwbGFuZHNjYXBlfGVufDF8fHx8MTc4NDI5NzI1OHww&ixlib=rb-4.1.0&q=80&w=1080') }}" 
            alt="Lanskap Desa" 
            class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-navy/90 via-navy/60 to-transparent mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-black/20"></div>
    </div>

    <div class="container relative z-10 mx-auto px-4 md:px-8 max-w-7xl pt-24 pb-32">
        <div class="max-w-3xl">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-yellow text-navy mb-6">
                Selamat Datang di
            </span>
            <h1 class="text-5xl md:text-7xl lg:text-[76px] font-bold text-white leading-[1.1] tracking-tight mb-6 font-display">
                {{ \App\Services\SettingsService::get('hero_title', 'Desa yang Tumbuh Bersama Masyarakat') }}
            </h1>
            <p class="text-lg md:text-xl text-white/90 mb-10 max-w-2xl leading-relaxed">
                {{ \App\Services\SettingsService::get('hero_description', 'Portal informasi resmi ' . \App\Services\SettingsService::get('village_name', 'Desa Kertajaya') . ' yang menghadirkan informasi, pelayanan, potensi, dan perkembangan desa secara terbuka untuk seluruh masyarakat.') }}
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <a href="#profil-desa" class="inline-flex items-center justify-center rounded-full font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-teal focus:ring-offset-2 bg-teal text-white hover:bg-teal/90 shadow-sm px-8 py-4 text-base">
                    Jelajahi Profil Desa
                </a>
                <a href="#berita" class="inline-flex items-center justify-center rounded-full font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-teal focus:ring-offset-2 border-2 border-white bg-white/10 text-white hover:bg-white hover:text-navy backdrop-blur-sm px-8 py-4 text-base">
                    Lihat Kabar Desa
                </a>
            </div>
        </div>
    </div>
</section>

<!-- QuickAccess Section -->
<div class="container mx-auto px-4 md:px-8 max-w-7xl relative z-20 -mt-16 md:-mt-24 mb-16">
    <div class="bg-cream backdrop-blur-md shadow-xl rounded-[24px] md:rounded-[32px] p-4 md:p-8">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @php
                $quickAccess = [
                    ['icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>', 'label' => 'Profil Desa', 'url' => '#profil-desa'],
                    ['icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>', 'label' => 'Potensi Desa', 'url' => '#potensi-desa'],
                    ['icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>', 'label' => 'Berita', 'url' => '#berita'],
                    ['icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>', 'label' => 'Galeri', 'url' => '#galeri'],
                    ['icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>', 'label' => 'Dokumen', 'url' => '#dokumen'],
                    ['icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>', 'label' => 'Pelayanan', 'url' => route('public.contact.show')],
                ];
            @endphp
            @foreach($quickAccess as $item)
            <a href="{{ $item['url'] }}" class="flex flex-col items-center justify-center p-4 md:p-6 rounded-2xl hover:bg-white transition-all duration-300 group gap-3 border border-transparent hover:border-gray-200 hover:shadow-sm">
                <div class="w-12 h-12 rounded-full bg-teal/10 flex items-center justify-center text-teal group-hover:bg-teal group-hover:text-white transition-colors">
                    {!! $item['icon'] !!}
                </div>
                <span class="font-medium text-sm text-navy">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>

<!-- Introduksi Section -->
<section id="profil-desa" class="py-16 md:py-24 bg-white !pt-0">
    <div class="container mx-auto px-4 md:px-8 max-w-7xl">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
            <div>
                <div class="mb-12 flex flex-col md:flex-row md:items-end gap-6 text-left md:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-teal font-semibold tracking-wider text-sm uppercase mb-3">Mengenal Desa</p>
                        <h2 class="text-3xl md:text-5xl font-bold mb-4 font-display">
                            {{ \App\Services\SettingsService::get('profil_title', 'Keindahan Alam dan Harmoni Masyarakat') }}
                        </h2>
                    </div>
                </div>
                <div class="space-y-6 text-gray-500 text-lg mb-8">
                    <p>
                        {{ \App\Services\SettingsService::get('profil_description', 'Desa Kertajaya terletak di dataran tinggi yang dikelilingi oleh perbukitan hijau dan hamparan sawah yang subur. Masyarakat kami hidup berdampingan dengan alam, memelihara tradisi luhur sambil terus bergerak maju mengikuti perkembangan zaman.') }}
                    </p>
                </div>
                <a href="#" class="inline-flex items-center justify-center rounded-full font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-teal focus:ring-offset-2 border-2 border-navy text-navy hover:bg-navy hover:text-white px-6 py-3 text-sm group">
                    Selengkapnya tentang desa
                    <svg class="ml-2 w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>

            <div class="relative">
                <div class="aspect-[4/5] rounded-[32px] overflow-hidden">
                    <img 
                        src="{{ $getMediaUrl(\App\Services\SettingsService::get('profil_image_1'), 'large', 'https://images.unsplash.com/photo-1661239733924-a1fa018b0737?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwzfHxpbmRvbmVzaWElMjB2aWxsYWdlJTIwbGFuZHNjYXBlfGVufDF8fHx8MTc4NDI5NzI1OHww&ixlib=rb-4.1.0&q=80&w=1080') }}"
                        alt="Pemandangan Desa"
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="absolute -bottom-8 -left-8 md:-bottom-12 md:-left-12 w-2/3 aspect-square rounded-[24px] overflow-hidden border-8 border-white shadow-lg hidden md:block">
                    <img 
                        src="{{ $getMediaUrl(\App\Services\SettingsService::get('profil_image_2'), 'medium', 'https://images.unsplash.com/photo-1711473610917-6f89130d41dd?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHw0fHxpbmRvbmVzaWElMjB2aWxsYWdlJTIwbGFuZHNjYXBlfGVufDF8fHx8MTc4NDI5NzI1OHww&ixlib=rb-4.1.0&q=80&w=1080') }}"
                        alt="Masyarakat Desa"
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="absolute top-6 right-6 md:-right-6 bg-white py-3 px-5 rounded-full shadow-lg flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-yellow flex items-center justify-center">
                        <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-medium">{{ \App\Services\SettingsService::get('address_subdistrict', 'Kecamatan') }}</p>
                        <p class="text-sm font-bold text-navy">{{ \App\Services\SettingsService::get('address_province', 'Jawa Barat') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PotensiDesa Section -->
<section id="potensi-desa" class="py-16 md:py-24 bg-cream">
    <div class="container mx-auto px-4 md:px-8 max-w-7xl">
        <div class="mb-12 flex flex-col md:flex-row md:items-end gap-6 text-left md:justify-between">
            <div class="max-w-2xl">
                <p class="text-teal font-semibold tracking-wider text-sm uppercase mb-3">Potensi Unggulan</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4 font-display">
                    {{ \App\Services\SettingsService::get('potensi_title', 'Kekayaan Alam dan Karya Masyarakat') }}
                </h2>
                <p class="text-gray-500 text-lg">
                    {{ \App\Services\SettingsService::get('potensi_description', 'Mengenali lebih dekat sumber daya alam dan kreativitas warga yang menjadi motor penggerak kesejahteraan desa.') }}
                </p>
            </div>
            <div class="shrink-0">
                <a href="{{ \App\Services\SettingsService::get('potensi_all_link', '#') }}" class="inline-flex items-center justify-center rounded-full font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-teal focus:ring-offset-2 border-2 border-navy text-navy hover:bg-navy hover:text-white px-6 py-3 text-sm">
                    Lihat Semua Potensi
                </a>
            </div>
        </div>

        @php
            $pot1 = [
                'title' => \App\Services\SettingsService::get('potensi_1_title', 'Pertanian & Perkebunan'),
                'desc' => \App\Services\SettingsService::get('potensi_1_desc', 'Hamparan sawah terasering dan perkebunan teh yang menjadi tulang punggung ekonomi warga.'),
                'image' => \App\Services\SettingsService::get('potensi_1_image', null),
                'link' => \App\Services\SettingsService::get('potensi_1_link', ''),
            ];
            $pot2 = [
                'title' => \App\Services\SettingsService::get('potensi_2_title', 'Pariwisata Alam'),
                'desc' => \App\Services\SettingsService::get('potensi_2_desc', 'Destinasi wisata curug dan desa wisata yang asri.'),
                'image' => \App\Services\SettingsService::get('potensi_2_image', null),
                'link' => \App\Services\SettingsService::get('potensi_2_link', ''),
            ];
            $pot3 = [
                'title' => \App\Services\SettingsService::get('potensi_3_title', 'UMKM Lokal'),
                'desc' => \App\Services\SettingsService::get('potensi_3_desc', 'Kerajinan bambu dan olahan makanan tradisional.'),
                'image' => \App\Services\SettingsService::get('potensi_3_image', null),
                'link' => \App\Services\SettingsService::get('potensi_3_link', ''),
            ];
        @endphp

        <div class="grid lg:grid-cols-2 gap-6 md:gap-8 h-auto lg:h-[600px] xl:h-[700px]">
            <!-- Large Card -->
            <a href="{{ $pot1['link'] ?: '#' }}" class="block relative rounded-[32px] overflow-hidden group h-[400px] md:h-[500px] lg:h-full">
                <img src="{{ $getMediaUrl($pot1['image'], 'large', 'https://images.unsplash.com/photo-1559628233-100c798642d4?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxpbmRvbmVzaWElMjBuYXR1cmUlMjBhZ3JpY3VsdHVyZXxlbnwxfHx8fDE3ODQyOTcyNTh8MA&ixlib=rb-4.1.0&q=80&w=1080') }}" alt="{{ $pot1['title'] }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                <div class="absolute inset-0 bg-gradient-to-t from-navy/90 via-navy/20 to-transparent"></div>
                <div class="absolute inset-0 p-8 flex flex-col justify-end">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-yellow text-navy w-fit mb-4">Potensi Utama</span>
                    <h3 class="text-3xl md:text-4xl font-bold text-white mb-3 font-display">{{ $pot1['title'] }}</h3>
                    <p class="text-white/80 text-lg max-w-md">{{ $pot1['desc'] }}</p>
                    <div class="mt-6 flex items-center justify-between">
                        <span class="text-white font-medium">Pelajari lebih lanjut</span>
                        <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-white group-hover:bg-white group-hover:text-navy transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </div>
                    </div>
                </div>
            </a>
            
            <!-- Small Cards -->
            <div class="grid grid-rows-2 gap-6 md:gap-8 h-[500px] md:h-[600px] lg:h-full">
                <!-- Card 2 -->
                <a href="{{ $pot2['link'] ?: '#' }}" class="block relative rounded-[32px] overflow-hidden group h-full">
                    <img src="{{ $getMediaUrl($pot2['image'], 'medium', 'https://images.unsplash.com/photo-1513415756790-2ac1db1297d0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwzfHxpbmRvbmVzaWElMjBuYXR1cmUlMjBhZ3JpY3VsdHVyZXxlbnwxfHx8fDE3ODQyOTcyNTh8MA&ixlib=rb-4.1.0&q=80&w=1080') }}" alt="{{ $pot2['title'] }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy/90 to-transparent"></div>
                    <div class="absolute inset-0 p-6 flex flex-col justify-end">
                        <h3 class="text-2xl font-bold text-white mb-2 font-display">{{ $pot2['title'] }}</h3>
                        <p class="text-white/80">{{ $pot2['desc'] }}</p>
                        <div class="mt-4 flex items-center gap-2 text-lime font-medium group-hover:text-white transition-colors">
                            <span>Lihat selengkapnya</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                    </div>
                </a>
                
                <!-- Card 3 -->
                <a href="{{ $pot3['link'] ?: '#' }}" class="block relative rounded-[32px] overflow-hidden group h-full">
                    <img src="{{ $getMediaUrl($pot3['image'], 'medium', 'https://images.unsplash.com/photo-1569134471968-872d5cd1fca9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwzfHxpbmRvbmVzaWElMjB2aWxsYWdlJTIwbGFuZHNjYXBlfGVufDF8fHx8MTc4NDI5NzI1OHww&ixlib=rb-4.1.0&q=80&w=1080') }}" alt="{{ $pot3['title'] }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy/90 to-transparent"></div>
                    <div class="absolute inset-0 p-6 flex flex-col justify-end">
                        <h3 class="text-2xl font-bold text-white mb-2 font-display">{{ $pot3['title'] }}</h3>
                        <p class="text-white/80">{{ $pot3['desc'] }}</p>
                        <div class="mt-4 flex items-center gap-2 text-lime font-medium group-hover:text-white transition-colors">
                            <span>Lihat selengkapnya</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <p class="text-center text-sm text-gray-400 mt-6 italic">Bagian Potensi Desa dapat disesuaikan lebih lanjut via modul khusus di masa depan.</p>
    </div>
</section>

<!-- Statistik Section -->
<section class="py-16 md:py-24 bg-navy text-white">
    <div class="container mx-auto px-4 md:px-8 max-w-7xl">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">
            <div class="flex flex-col items-center md:items-start border-l border-white/20 pl-6">
                <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-lime mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-white mb-2 font-display">{{ \App\Services\SettingsService::get('stat_population', '3.450') }}</div>
                <div class="text-white/70 font-medium">Jumlah Penduduk</div>
            </div>
            <div class="flex flex-col items-center md:items-start border-l border-white/20 pl-6">
                <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-lime mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-white mb-2 font-display">{{ \App\Services\SettingsService::get('stat_families', '850') }}</div>
                <div class="text-white/70 font-medium">Kepala Keluarga</div>
            </div>
            <div class="flex flex-col items-center md:items-start border-l border-white/20 pl-6">
                <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-lime mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-white mb-2 font-display">{{ \App\Services\SettingsService::get('stat_area', '1.250') }}</div>
                <div class="text-white/70 font-medium">Luas Wilayah (Ha)</div>
            </div>
            <div class="flex flex-col items-center md:items-start border-l border-white/20 pl-6">
                <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-lime mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-white mb-2 font-display">{{ \App\Services\SettingsService::get('stat_hamlets', '4') }}</div>
                <div class="text-white/70 font-medium">Jumlah Dusun</div>
            </div>
        </div>
    </div>
</section>

<!-- BeritaTerbaru Section -->
@if(isset($latestNews) && $latestNews->isNotEmpty())
<section id="berita" class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4 md:px-8 max-w-7xl">
        <div class="mb-12 flex flex-col md:flex-row md:items-end gap-6 text-left md:justify-between">
            <div class="max-w-2xl">
                <p class="text-teal font-semibold tracking-wider text-sm uppercase mb-3">Kabar Terbaru</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4 font-display">Berita & Pengumuman Desa</h2>
            </div>
            <div class="shrink-0">
                <a href="{{ route('news.index') }}" class="inline-flex items-center justify-center rounded-full font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-teal focus:ring-offset-2 border-2 border-navy text-navy hover:bg-navy hover:text-white px-6 py-3 text-sm">
                    Indeks Berita
                </a>
            </div>
        </div>
        
        <div class="grid lg:grid-cols-12 gap-8">
            <!-- Main News Card -->
            @php $mainNews = $latestNews->first(); @endphp
            @if($mainNews)
            <a href="{{ route('news.show', $mainNews->slug) }}" class="lg:col-span-7 group cursor-pointer block">
                <div class="rounded-[24px] overflow-hidden aspect-[4/3] md:aspect-[16/9] mb-6 relative bg-gray-100">
                    @if($mainNews->featuredMedia && $mainNews->featuredMedia->invisible_watermark_status === 'verified' && ($deriv = $mainNews->featuredMedia->getPublicDerivative('large')))
                        <img src="{{ Storage::disk('public')->url($deriv->file_path) }}" alt="{{ $mainNews->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    @else
                        <div class="w-full h-full bg-gray-200 transition-transform duration-500 group-hover:scale-105 flex items-center justify-center text-gray-400">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    @endif
                </div>
                <div class="flex items-center gap-4 text-sm font-medium text-gray-500 mb-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border border-navy text-navy">
                        {{ $mainNews->category?->name ?? 'Berita' }}
                    </span>
                    <span>{{ $mainNews->published_at->format('d M Y') }}</span>
                </div>
                <h3 class="text-3xl font-bold text-navy mb-4 group-hover:text-teal transition-colors font-display">
                    {{ $mainNews->title }}
                </h3>
                <p class="text-lg text-gray-500 mb-6 line-clamp-2">{{ Str::limit(strip_tags($mainNews->content), 150) }}</p>
                <div class="font-semibold text-teal flex items-center gap-2">
                    Baca Selengkapnya <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </div>
            </a>
            @endif

            <!-- Side News Cards -->
            <div class="lg:col-span-5 flex flex-col gap-8">
                @foreach($latestNews->skip(1)->take(3) as $item)
                <a href="{{ route('news.show', $item->slug) }}" class="group cursor-pointer flex flex-col sm:flex-row gap-6 items-start">
                    <div class="rounded-[16px] overflow-hidden w-full sm:w-48 shrink-0 aspect-[4/3] relative bg-gray-100">
                        @if($item->featuredMedia && $item->featuredMedia->invisible_watermark_status === 'verified' && ($deriv = $item->featuredMedia->getPublicDerivative('medium')))
                            <img src="{{ Storage::disk('public')->url($deriv->file_path) }}" alt="{{ $item->title }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                        @else
                            <div class="w-full h-full bg-gray-200 transition-transform duration-500 group-hover:scale-105 flex items-center justify-center text-gray-400">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-3 text-xs font-medium text-gray-500 mb-3">
                            <span class="text-teal font-bold">{{ $item->category?->name ?? 'Berita' }}</span>
                            <span>&bull;</span>
                            <span>{{ $item->published_at->format('d M Y') }}</span>
                        </div>
                        <h4 class="text-xl font-bold text-navy mb-2 line-clamp-2 group-hover:text-teal transition-colors font-display">
                            {{ $item->title }}
                        </h4>
                        <p class="text-gray-500 line-clamp-2 mb-3 text-sm">{{ Str::limit(strip_tags($item->content), 80) }}</p>
                        <div class="text-sm font-semibold text-navy flex items-center gap-1 group-hover:text-teal">
                            Baca <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<!-- GaleriDesa Section -->
@if(isset($latestAlbums) && $latestAlbums->isNotEmpty())
<section id="galeri" class="py-16 md:py-24 bg-cream">
    <div class="container mx-auto px-4 md:px-8 max-w-7xl">
        <div class="mb-12 flex flex-col md:flex-row md:items-center gap-6 text-center md:justify-center">
            <div class="max-w-2xl mx-auto">
                <p class="text-teal font-semibold tracking-wider text-sm uppercase mb-3">Galeri Desa</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4 font-display">Rekaman Visual Kehidupan</h2>
                <p class="text-gray-500 text-lg">Dokumentasi kegiatan, keindahan alam, dan momen kebersamaan masyarakat Desa Kertajaya.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 grid-rows-[300px_200px_200px] md:grid-rows-2 gap-4 md:gap-6 h-auto md:h-[700px]">
            @php $count = 0; @endphp
            @foreach($latestAlbums->take(4) as $idx => $album)
                @php $count++; @endphp
                @if($idx === 0)
                    <!-- Main large album -->
                    <a href="{{ route('gallery.show', $album->slug) }}" class="col-span-2 row-span-1 md:row-span-2 rounded-[24px] overflow-hidden group relative block bg-gray-200">
                        @if($album->coverMedia && $album->coverMedia->invisible_watermark_status === 'verified' && ($deriv = $album->coverMedia->getPublicDerivative('large')))
                            <img src="{{ Storage::disk('public')->url($deriv->file_path) }}" alt="{{ $album->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                        @else
                            <div class="w-full h-full bg-gray-300 flex items-center justify-center text-gray-400">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-colors"></div>
                        <div class="absolute bottom-6 left-6 text-white">
                            <h4 class="font-bold text-xl font-display">{{ $album->title }}</h4>
                            <p class="text-sm text-white/80">{{ $album->published_at->format('d M Y') }}</p>
                        </div>
                    </a>
                @elseif($idx === 1)
                    <!-- Second album -->
                    <a href="{{ route('gallery.show', $album->slug) }}" class="col-span-2 row-span-1 rounded-[24px] overflow-hidden group relative block bg-gray-200">
                        @if($album->coverMedia && $album->coverMedia->invisible_watermark_status === 'verified' && ($deriv = $album->coverMedia->getPublicDerivative('medium')))
                            <img src="{{ Storage::disk('public')->url($deriv->file_path) }}" alt="{{ $album->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                        @else
                            <div class="w-full h-full bg-gray-300 flex items-center justify-center text-gray-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-colors"></div>
                        <div class="absolute bottom-4 left-4 text-white">
                            <h4 class="font-bold text-lg font-display">{{ $album->title }}</h4>
                        </div>
                    </a>
                @elseif($idx === 2)
                    <!-- Third album -->
                    <a href="{{ route('gallery.show', $album->slug) }}" class="col-span-1 row-span-1 rounded-[24px] overflow-hidden group relative block bg-gray-200">
                        @if($album->coverMedia && $album->coverMedia->invisible_watermark_status === 'verified' && ($deriv = $album->coverMedia->getPublicDerivative('medium')))
                            <img src="{{ Storage::disk('public')->url($deriv->file_path) }}" alt="{{ $album->title }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
                        @else
                            <div class="w-full h-full bg-gray-300 flex items-center justify-center text-gray-400">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/40 transition-colors"></div>
                    </a>
                @endif
            @endforeach
            
            <!-- Link to all galleries -->
            <a href="{{ route('gallery.index') }}" class="col-span-1 row-span-1 rounded-[24px] flex items-center justify-center bg-navy text-white group relative overflow-hidden block">
                <div class="relative z-10 text-center flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full border border-white/30 flex items-center justify-center mb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                    <span class="font-bold">Lihat Semua<br/>Galeri</span>
                </div>
            </a>
        </div>
    </div>
</section>
@endif

<!-- DokumenPublik Section -->
@if(isset($latestDocuments) && $latestDocuments->isNotEmpty())
<section id="dokumen" class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-4 md:px-8 max-w-7xl">
        <div class="mb-12 flex flex-col md:flex-row md:items-end gap-6 text-left md:justify-between">
            <div class="max-w-2xl">
                <p class="text-teal font-semibold tracking-wider text-sm uppercase mb-3">Transparansi & Dokumen</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4 font-display">Dokumen Publik Desa</h2>
            </div>
            <div class="shrink-0">
                <a href="{{ route('documents.index') }}" class="inline-flex items-center justify-center rounded-full font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-teal focus:ring-offset-2 border-2 border-navy text-navy hover:bg-navy hover:text-white px-6 py-3 text-sm">
                    Semua Dokumen
                </a>
            </div>
        </div>

        <div class="bg-white rounded-[24px] border border-gray-200 shadow-sm overflow-hidden">
            @foreach($latestDocuments as $idx => $doc)
            <div class="flex flex-col md:flex-row md:items-center justify-between p-6 md:p-8 gap-6 hover:bg-cream/50 transition-colors {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                <div class="flex items-start gap-4 md:gap-6 flex-1">
                    <div class="w-12 h-12 rounded-full bg-teal/10 flex items-center justify-center text-teal shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider border border-teal text-teal">{{ $doc->category?->name ?? 'Dokumen' }}</span>
                            <span class="text-sm text-gray-500">{{ $doc->published_at->format('d M Y') }}</span>
                        </div>
                        <h4 class="font-bold text-lg md:text-xl text-navy leading-snug">{{ $doc->title }}</h4>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 self-end md:self-auto shrink-0 border-t border-gray-200 pt-4 md:border-0 md:pt-0 w-full md:w-auto">
                    <a href="{{ route('documents.download', $doc->slug) }}" target="_blank" class="flex-1 md:flex-none inline-flex items-center justify-center rounded-full font-medium transition-colors bg-teal text-white hover:bg-teal/90 shadow-sm px-6 py-3 text-sm gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Unduh
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- FinalCTA Section -->
<section class="relative overflow-hidden text-center py-24 md:py-32 bg-teal text-white">
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-10 pointer-events-none">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[150%] bg-white rounded-full blur-[100px] transform rotate-12"></div>
        <div class="absolute top-[30%] -right-[10%] w-[40%] h-[120%] bg-lime rounded-full blur-[120px] transform -rotate-12"></div>
    </div>

    <div class="relative z-10 max-w-3xl mx-auto px-4">
        <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 font-display">
            Punya pertanyaan atau membutuhkan informasi?
        </h2>
        <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
            Hubungi pemerintah {{ \App\Services\SettingsService::get('village_name', 'Desa Kertajaya') }} untuk mendapatkan pelayanan publik, informasi data, atau bantuan yang Anda perlukan.
        </p>
        <a href="{{ route('public.contact.show') }}" class="inline-flex items-center justify-center rounded-full font-medium transition-colors bg-yellow text-navy hover:bg-yellow/90 shadow-sm px-8 py-4 text-lg">
            Lihat Informasi Kontak
        </a>
    </div>
</section>

@endsection
