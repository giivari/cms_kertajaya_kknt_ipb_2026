<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AddExportDownloadSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->routeIs('filament.exports.download', 'admin.exports.pdf.download')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
