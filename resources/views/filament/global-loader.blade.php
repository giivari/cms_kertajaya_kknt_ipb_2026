<div id="global-filament-loader" class="fixed inset-0 z-[99999] bg-gray-900/80 backdrop-blur-sm flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="relative w-16 h-16 mb-4">
        <div class="absolute inset-0 rounded-full border-4 border-white/20"></div>
        <div class="absolute inset-0 rounded-full border-4 border-primary-500 border-t-transparent animate-spin"></div>
    </div>
    <span class="text-white font-medium text-lg tracking-wide animate-pulse">Sedang memproses...</span>
</div>

<script>
    (function() {
        const loader = document.getElementById('global-filament-loader');
        if (!loader) return;
        
        let activeRequests = 0;
        let loaderTimeout;
        
        function showLoader() {
            activeRequests++;
            if (activeRequests === 1) {
                clearTimeout(loaderTimeout);
                loader.classList.remove('opacity-0', 'pointer-events-none');
                loader.classList.add('opacity-100');
            }
        }
        
        function hideLoader() {
            activeRequests--;
            if (activeRequests <= 0) {
                activeRequests = 0;
                loaderTimeout = setTimeout(() => {
                    if (activeRequests === 0) {
                        loader.classList.remove('opacity-100');
                        loader.classList.add('opacity-0', 'pointer-events-none');
                    }
                }, 100);
            }
        }

        let hooksRegistered = false;
        function registerLivewireHooks() {
            if (hooksRegistered || typeof window.Livewire === 'undefined') return;
            hooksRegistered = true;
            
            Livewire.hook('commit', ({ commit, succeed, fail }) => {
                showLoader();
                succeed(() => hideLoader());
                fail(() => hideLoader());
            });
        }

        // Try immediately
        registerLivewireHooks();

        // Listen for standard Livewire events
        document.addEventListener('livewire:init', registerLivewireHooks);
        document.addEventListener('livewire:initialized', registerLivewireHooks);

        document.addEventListener('livewire:navigating', () => {
            showLoader();
        });
        
        document.addEventListener('livewire:navigated', () => {
            hideLoader();
            activeRequests = 0; 
        });

        // Catch standard interactions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form.target || form.target !== '_blank') {
                showLoader();
            }
        });
        
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('button');
            if (btn && (btn.hasAttribute('wire:click') || btn.type === 'submit')) {
                showLoader();
            }
        });
    })();
</script>
