<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $siteKey = config('services.turnstile.key');
        if (app()->environment('local', 'testing') || empty($siteKey)) {
            $siteKey = '1x00000000000000000000AA'; // Cloudflare dummy testing key
        }
    @endphp

    <div x-data="{
             widgetId: null,
             init() {
                 const renderTurnstile = () => {
                     if (typeof turnstile === 'undefined') {
                         setTimeout(renderTurnstile, 100);
                         return;
                     }
                     this.widgetId = turnstile.render($refs.turnstileContainer, {
                         sitekey: '{{ $siteKey }}',
                         callback: (token) => {
                             $wire.set('{{ $getStatePath() }}', token);
                         },
                         'error-callback': () => {
                             $wire.set('{{ $getStatePath() }}', null);
                         },
                         'expired-callback': () => {
                             $wire.set('{{ $getStatePath() }}', null);
                             turnstile.reset(this.widgetId);
                         }
                     });
                 };
                 renderTurnstile();
                 
                 window.addEventListener('turnstile-reset', () => {
                     if (this.widgetId !== null && typeof turnstile !== 'undefined') {
                         turnstile.reset(this.widgetId);
                         $wire.set('{{ $getStatePath() }}', null);
                     }
                 });
             }
         }"
         wire:ignore
    >
        @if ($siteKey)
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
            <div x-ref="turnstileContainer"></div>
        @else
            <div class="text-sm text-red-600 bg-red-50 p-2 rounded border border-red-200">
                Verifikasi keamanan tidak tersedia. Sistem tidak dapat memproses form ini.
            </div>
        @endif
    </div>
</x-dynamic-component>