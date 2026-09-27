<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMfaRecoveryVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');

        if (! $guard->check()) {
            return $next($request);
        }

        $version = (int) $guard->user()->mfa_recovery_version;
        $sessionVersion = $request->session()->get('admin_mfa_recovery_version');

        // Existing sessions predate this additive field. Once recovery increments
        // the version, a missing or stale marker can never authenticate again.
        if (($version === 0 && $sessionVersion === null) || $sessionVersion === $version) {
            return $next($request);
        }

        $guard->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login');
    }
}
