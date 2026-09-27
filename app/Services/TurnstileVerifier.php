<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerifier
{
    public function verify(#[\SensitiveParameter] ?string $token, ?string $ip = null): bool
    {
        $secret = config('services.turnstile.secret');

        if (! is_string($secret) || trim($secret) === '' || preg_match('/\s/', $secret)) {
            Log::error('Turnstile configuration failure: missing or malformed secret');
            abort(503, 'Verifikasi keamanan belum dikonfigurasi dengan benar. Hubungi pengelola.');
        }

        if (empty($token)) {
            Log::warning('Turnstile token empty');
            return false;
        }

        try {
            $response = Http::timeout(5)->asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            if ($response->successful() && is_array($response->json()) && $response->json('success') === true) {
                return true;
            }

            if (! $response->successful() || ! is_bool($response->json('success'))) {
                Log::warning('Turnstile verification service failure', ['status' => $response->status()]);
            }

            return false;
        } catch (\Throwable $e) {
            // HTTP exception messages can include request data; never log them.
            Log::error('Turnstile verification transport failure', ['exception_type' => $e::class]);
            return false;
        }
    }
}
