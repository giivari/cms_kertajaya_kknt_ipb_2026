<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerifier
{
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (app()->environment('local', 'testing')) {
            return true;
        }

        if (empty($token)) {
            Log::warning('Turnstile token empty');
            return false;
        }

        $secret = config('services.turnstile.secret');

        if (empty($secret)) {
            Log::warning('Turnstile secret empty, bypassing to prevent lockout');
            return true;
        }

        try {
            $response = Http::timeout(5)->asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            Log::info('Turnstile response', ['status' => $response->status(), 'body' => $response->json()]);

            if ($response->successful() && is_array($response->json()) && $response->json('success') === true) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('Turnstile exception', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
