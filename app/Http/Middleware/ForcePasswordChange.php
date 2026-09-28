<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Filament::auth()->check()) {
            /** @var Admin $user */
            $user = Filament::auth()->user();

            if ($user->force_password_change) {
                $profileUrl = filament()->getProfileUrl();
                // Livewire resolves this route from a verified snapshot, not a
                // component name supplied in the unverified request body.
                if (! $request->routeIs('filament.admin.auth.profile')
                    && ! $request->routeIs('filament.admin.auth.logout')
                    && ! $request->routeIs('filament.admin.auth.multi-factor-authentication.*')) {
                    return redirect($profileUrl);
                }
            }
        }

        return $next($request);
    }
}
