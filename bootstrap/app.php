<?php

use App\Http\Middleware\AddExportDownloadSecurityHeaders;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->append(AddExportDownloadSecurityHeaders::class);
        $middleware->group('admin.security', [
            'panel:admin',
            \Filament\Http\Middleware\Authenticate::class,
            \Filament\Http\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\EnsureAdminMfaRecoveryVersion::class,
            \App\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\AbsoluteSessionTimeout::class,
            \Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(fn ($response) => app(SecurityHeaders::class)->apply(request(), $response));
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
