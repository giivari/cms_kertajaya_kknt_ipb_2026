<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{
             init() {
                 const renderTurnstile = () => {
                     if (typeof turnstile === 'undefined') {
                         setTimeout(renderTurnstile, 100);
                         return;
                     }
                     turnstile.render($refs.turnstileContainer, {
                         sitekey: '{{ config('services.turnstile.key') }}',
                         callback: (token) => {
                             $wire.set('{{ $getStatePath() }}', token);
                         },
                         'error-callback': () => {
                             $wire.set('{{ $getStatePath() }}', null);
                         },
                         'expired-callback': () => {
                             $wire.set('{{ $getStatePath() }}', null);
                         }
                     });
                 };
                 renderTurnstile();
             }
         }"
         wire:ignore
    >
        @if (config('services.turnstile.key'))
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
            <div x-ref="turnstileContainer"></div>
        @else
            <div class="text-sm text-red-600 bg-red-50 p-2 rounded border border-red-200">
                Verifikasi keamanan tidak tersedia. Sistem tidak dapat memproses form ini.
            </div>
        @endif
    </div>
</x-dynamic-component>