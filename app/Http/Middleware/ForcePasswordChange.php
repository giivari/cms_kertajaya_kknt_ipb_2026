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
                // Ensure we are not already on the profile page or logout to avoid redirect loop
                $profileRoute = Filament::getCurrentPanel()->getRouteName('auth.profile');
                $logoutRoute = Filament::getCurrentPanel()->getRouteName('auth.logout');

                if (! $request->routeIs($profileRoute) && ! $request->routeIs($logoutRoute)) {
                    return redirect()->route($profileRoute);
                }
            }
        }

        return $next($request);
    }
}
