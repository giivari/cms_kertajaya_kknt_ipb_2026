<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }"
         x-init="
            window.turnstileCallback = function(token) {
                state = token;
            };
         "
         wire:ignore
    >
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile" data-sitekey="{{ env('TURNSTILE_SITE_KEY', '1x00000000000000000000AA') }}" data-callback="turnstileCallback"></div>
    </div>
</x-dynamic-component>
