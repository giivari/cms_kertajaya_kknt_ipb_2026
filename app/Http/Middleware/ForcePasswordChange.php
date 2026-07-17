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
                $logoutUrl = filament()->getLogoutUrl();

                if (app('livewire')->isLivewireRequest()) {
                    $components = $request->input('components', []);
                    foreach ($components as $component) {
                        $snapshot = json_decode($component['snapshot'] ?? '{}', true);
                        $name = $snapshot['memo']['name'] ?? '';

                        if ($name === 'app.filament.pages.auth.edit-profile') {
                            continue;
                        }

                        return redirect($profileUrl);
                    }
                } elseif (! $request->routeIs('filament.admin.auth.profile') && ! $request->routeIs('filament.admin.auth.logout')) {
                    return redirect($profileUrl);
                }
            }
        }

        return $next($request);
    }
}
