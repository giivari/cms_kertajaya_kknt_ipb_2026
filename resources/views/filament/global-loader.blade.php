<div id="global-filament-loader" class="fixed inset-0 z-[99999] bg-gray-900/80 backdrop-blur-sm flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
    <div class="relative w-16 h-16 mb-4">
        <div class="absolute inset-0 rounded-full border-4 border-white/20"></div>
        <div class="absolute inset-0 rounded-full border-4 border-primary-500 border-t-transparent animate-spin"></div>
    </div>
    <span class="text-white font-medium text-lg tracking-wide animate-pulse">Sedang memproses...</span>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const loader = document.getElementById('global-filament-loader');
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
                }, 300);
            }
        }

        // Filament / Livewire v3 Hooks
        if (typeof document.addEventListener !== 'undefined') {
            document.addEventListener('livewire:init', () => {
                Livewire.hook('commit', ({ commit, succeed, fail }) => {
                    // Tampilkan loader untuk semua action (simpan, pratinjau, dll)
                    if (commit.calls.length > 0) {
                        showLoader();
                        succeed(() => hideLoader());
                        fail(() => hideLoader());
                    }
                });
            });

            // Hook untuk navigasi halaman SPA
            document.addEventListener('livewire:navigating', () => {
                showLoader();
            });
            
            document.addEventListener('livewire:navigated', () => {
                hideLoader();
                activeRequests = 0; 
            });

            // Menangkap form biasa (misal halaman login)
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form.target || form.target !== '_blank') {
                    showLoader();
                }
            });
        }
    });
</script>
