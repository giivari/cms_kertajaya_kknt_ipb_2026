@props([
    'previewUrl',
    'title' => 'Pratinjau',
])

<div x-data="{ mode: 'desktop' }" class="flex flex-col h-full bg-gray-100 dark:bg-gray-900 w-full overflow-hidden rounded-lg" style="min-height: 80vh;">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm shrink-0">
        <div class="flex items-center gap-4">
            {{-- Close / Back --}}
            <button
                type="button"
                aria-label="Tutup Pratinjau"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition"
                x-on:click="close()"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <span class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ $title }}</span>
        </div>

        <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-900 p-1 rounded-lg">
            {{-- Desktop Mode --}}
            <button
                type="button"
                aria-label="Mode Desktop"
                :aria-pressed="mode === 'desktop'"
                class="p-2 rounded-md transition"
                :class="mode === 'desktop' ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="mode = 'desktop'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </button>

            {{-- Tablet Mode --}}
            <button
                type="button"
                aria-label="Mode Tablet"
                :aria-pressed="mode === 'tablet'"
                class="p-2 rounded-md transition"
                :class="mode === 'tablet' ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="mode = 'tablet'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </button>

            {{-- Mobile Mode --}}
            <button
                type="button"
                aria-label="Mode Mobile"
                :aria-pressed="mode === 'mobile'"
                class="p-2 rounded-md transition"
                :class="mode === 'mobile' ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                x-on:click="mode = 'mobile'"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </button>
        </div>

        <div>
            {{-- Open in New Tab --}}
            <a
                href="{{ $previewUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Buka di Tab Baru
            </a>
        </div>
    </div>

    {{-- Iframe Container --}}
    <div class="flex-1 w-full flex justify-center items-start overflow-hidden bg-gray-200 dark:bg-gray-950 p-2 sm:p-4">
        <div
            class="h-full transition-all duration-300 ease-in-out bg-white shadow-lg overflow-hidden border border-gray-300 dark:border-gray-700 flex flex-col"
            :class="{
                'w-full max-w-full rounded-md': mode === 'desktop',
                'w-[768px] rounded-xl': mode === 'tablet',
                'w-[390px] rounded-3xl': mode === 'mobile'
            }"
        >
            <iframe
                src="{{ $previewUrl }}"
                title="{{ $title }}"
                sandbox="allow-scripts allow-same-origin"
                referrerpolicy="no-referrer"
                class="flex-1 w-full h-full border-0 bg-white"
            ></iframe>
        </div>
    </div>
</div>
