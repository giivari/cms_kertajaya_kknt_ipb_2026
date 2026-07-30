<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerifier
{
    /**
     * Verify the given Turnstile token.
     * 
     * @param string|null $token
     * @param string|null $ip
     * @return bool Returns true if valid, false if invalid or on failure (fail-closed).
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (empty($token)) {
            return false;
        }

        $secret = config('services.turnstile.secret');

        if (empty($secret)) {
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

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}