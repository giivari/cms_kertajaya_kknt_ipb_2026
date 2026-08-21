@php
    // Parameters:
    // $title (string)
    // $description (string|null)
    // $breadcrumbs (array|null) e.g. [['label' => 'Beranda', 'url' => route('home')], ['label' => 'Berita', 'url' => null]]
    // $bgImage (string|null)
@endphp

<div class="relative bg-navy pb-12 pt-28 md:pt-36 lg:pt-40 text-white overflow-hidden">
    {{-- Background Image or Pattern --}}
    @if(!empty($bgImage))
        <div class="absolute inset-0 z-0">
            <img src="{{ $bgImage }}" alt="{{ $title }}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-navy-900/70 via-navy-800/40 to-transparent"></div>
            <div class="absolute inset-0 bg-black/20"></div>
        </div>
    @else
        <div class="absolute inset-0 z-0 bg-navy">
            <div class="absolute inset-0 bg-gradient-to-br from-teal-900/40 to-navy-900/80"></div>
            {{-- Optional subtle texture --}}
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:20px_20px]"></div>
        </div>
    @endif

    <div class="relative z-10 w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32 flex flex-col justify-end min-h-[120px] md:min-h-[160px]">
        
        {{-- Breadcrumbs --}}
        @if(!empty($breadcrumbs))
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2 text-sm md:text-base font-medium">
                    @foreach($breadcrumbs as $index => $crumb)
                        <li class="inline-flex items-center">
                            @if($index > 0)
                                <svg class="w-4 h-4 mx-1 md:mx-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            @endif
                            
                            @if(!empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}" class="text-gray-200 hover:text-white transition-colors">
                                    {{ $crumb['label'] }}
                                </a>
                            @else
                                <span class="text-white">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        {{-- Title --}}
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold tracking-tight text-white drop-shadow-md font-display mb-2">
            {{ $title }}
        </h1>
        
        {{-- Description --}}
        @if(!empty($description))
            <p class="max-w-3xl text-lg md:text-xl text-gray-200 drop-shadow">
                {{ $description }}
            </p>
        @endif
    </div>
</div>
