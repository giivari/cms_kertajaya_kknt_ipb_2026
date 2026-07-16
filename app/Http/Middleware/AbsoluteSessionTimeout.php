<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AbsoluteSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            $sessionCreated = $request->session()->get('session_created_at');
            $maxLifetime = 8 * 60 * 60; // 8 hours in seconds

            if (! $sessionCreated) {
                $request->session()->put('session_created_at', time());
            } elseif (time() - $sessionCreated > $maxLifetime) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('filament.admin.auth.login')->withErrors([
                    'username' => 'Your session has expired for security reasons. Please log in again.',
                ]);
            }
        }

        return $next($request);
    }
}
