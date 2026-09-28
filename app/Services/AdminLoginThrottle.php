<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

final class AdminLoginThrottle
{
    private const WINDOW_SECONDS = 900;

    public function key(string $username, ?string $ip): string
    {
        return 'admin-login:'.hash('sha256', mb_strtolower(mb_substr(trim($username), 0, 255)).'|'.($ip ?? 'unknown'));
    }

    public function waitSeconds(string $key): int
    {
        $lockout = (int) Cache::get($key.':locked_until', 0) - now()->timestamp;
        if ($lockout > 0) {
            return $lockout;
        }
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return max(1, RateLimiter::availableIn($key));
        }

        return max(0, (int) Cache::get($key.':next', 0) - now()->timestamp);
    }

    public function failed(string $key): void
    {
        RateLimiter::hit($key, self::WINDOW_SECONDS);
        $attempts = RateLimiter::attempts($key);
        if ($attempts >= 5) {
            Cache::put($key.':locked_until', now()->timestamp + self::WINDOW_SECONDS, self::WINDOW_SECONDS);
        }
        $delay = [1, 3, 5, 10][$attempts - 1] ?? self::WINDOW_SECONDS;
        Cache::put($key.':next', now()->timestamp + $delay, self::WINDOW_SECONDS);
    }

    public function succeeded(string $key): void
    {
        RateLimiter::clear($key);
        Cache::forget($key.':next');
        Cache::forget($key.':locked_until');
    }
}
