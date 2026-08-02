<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreviewToken;
use App\Services\Preview\PreviewTokenStore;
use Illuminate\Http\Request;
use App\Support\Preview\PreviewContext;
use App\Support\Preview\Renderers\NewsPreviewRenderer;
use Filament\Facades\Filament;

class PreviewController extends Controller
{
    public function __construct(
        protected PreviewTokenStore $tokenStore
    ) {}

    public function show(Request $request, string $token)
    {
        $admin = Filament::auth()->user();
        if (!$admin) {
            abort(404);
        }

        $sessionId = $request->session()->getId();

        $payload = $this->tokenStore->retrieve($token, $admin->id, $sessionId);

        if ($payload === null) {
            abort(404);
        }

        $record = PreviewToken::where('token_hash', hash('sha256', $token))->first();
        $type = $record ? $record->preview_type : 'unknown';

        if ($type === 'news') {
            $context = PreviewContext::fromPayload($payload, ['token' => $token]);
            $renderer = new NewsPreviewRenderer();
            $view = $renderer->render($context);
            $response = response($view);
        } else {
            $response = response()->view('public.preview.placeholder', [
                'type' => htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            ]);
        }

        $response->headers->set('Cache-Control', 'no-store, private, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", false);

        return $response;
    }
}

