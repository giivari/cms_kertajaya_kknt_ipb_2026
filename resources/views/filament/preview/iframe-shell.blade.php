<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .preview-frame {
            max-width: none !important;
            transition: transform 0.3s ease-in-out;
        }
        .frame-desktop { width: 1024px !important; flex-shrink: 0 !important; border-radius: 0.375rem 0.375rem 0 0; }
        .frame-tablet { width: 768px !important; flex-shrink: 0 !important; border-radius: 1rem; border: 8px solid #d1d5db; }
        .frame-mobile { width: 390px !important; flex-shrink: 0 !important; border-radius: 2.5rem; border: 12px solid #d1d5db; }
        .dark .frame-tablet, .dark .frame-mobile { border-color: #374151; }
        @media (max-width: 640px) {
            .frame-tablet, .frame-mobile, .frame-desktop {
                border: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 m-0 p-0 overflow-hidden h-screen flex flex-col">
    <div x-data="{ 
        mode: 'desktop', 
        scale: 1,
        updateScale() {
            let sw = window.innerWidth;
            let padding = sw >= 640 ? 32 : 0; 
            let availableW = sw - padding;
            let targetW = this.mode === 'desktop' ? 1024 : (this.mode === 'tablet' ? 768 : 390);
            
            if (availableW < targetW) {
                this.scale = availableW / targetW;
            } else {
                this.scale = 1;
            }
        },
        reloadIframe() { document.getElementById('preview-iframe').src = document.getElementById('preview-iframe').src; } 
    }" 
    x-init="updateScale(); window.addEventListener('resize', () => updateScale()); $watch('mode', () => updateScale())"
    class="flex flex-col h-full w-full">
        <!-- Toolbar -->
        <div class="flex items-center justify-between px-2 py-2 sm:px-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm shrink-0">
            
            <!-- 1. Title (Left) -->
            <div class="flex-1 shrink-0 truncate pr-2">
                <span class="font-medium text-gray-900 dark:text-white truncate" aria-label="Tutup Pratinjau">{{ $title }}</span>
            </div>

            <!-- 2. Mode Switcher (Right on Mobile, Center on Desktop) -->
            <div class="mode-switcher flex items-center justify-center gap-1 sm:gap-2 shrink-0 bg-gray-100 dark:bg-gray-900 p-1 rounded-lg">
                <button type="button" aria-label="Desktop" :aria-pressed="mode === 'desktop'" class="p-2 rounded-md transition" :class="mode === 'desktop' ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700'" x-on:click="mode = 'desktop'; setTimeout(reloadIframe, 300)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </button>
                <button type="button" aria-label="Tablet" :aria-pressed="mode === 'tablet'" class="p-2 rounded-md transition" :class="mode === 'tablet' ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700'" x-on:click="mode = 'tablet'; setTimeout(reloadIframe, 300)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </button>
                <button type="button" aria-label="Mobile" :aria-pressed="mode === 'mobile'" class="p-2 rounded-md transition" :class="mode === 'mobile' ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700'" x-on:click="mode = 'mobile'; setTimeout(reloadIframe, 300)">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </button>
            </div>

            <!-- 3. Spacer (Right - Only on Desktop to center the switcher) -->
            <div class="hidden sm:block flex-1"></div>
        </div>

        <!-- Iframe Container -->
        <div class="flex-1 w-full flex justify-center items-start overflow-hidden bg-gray-200 dark:bg-gray-950 pt-0 sm:pt-4 pb-0 sm:pb-4 sm:px-4">
            <div class="transition-all duration-300 ease-in-out bg-white shadow-xl overflow-hidden flex flex-col preview-frame"
                :style="`transform: scale(${scale}); transform-origin: top center; height: ${scale < 1 ? (100 / scale) : 100}%;`"
                :class="{
                    'frame-desktop': mode === 'desktop',
                    'frame-tablet': mode === 'tablet',
                    'frame-mobile': mode === 'mobile'
                }">
                <iframe id="preview-iframe" src="{{ $previewUrl }}" sandbox="allow-scripts allow-same-origin" referrerpolicy="no-referrer" class="flex-1 w-full h-full border-0 bg-white"></iframe>
            </div>
        </div>
    </div>
</body>
</html>
