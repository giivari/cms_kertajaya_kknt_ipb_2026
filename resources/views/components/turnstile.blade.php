@if (config('services.turnstile.key'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.key') }}" data-callback="turnstileCallback"></div>
@else
    <div class="text-sm text-red-600 bg-red-50 p-2 rounded border border-red-200">
        Verifikasi keamanan tidak tersedia. Sistem tidak dapat menerima pengiriman formulir saat ini.
    </div>
@endif