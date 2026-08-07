<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreviewToken;
use App\Services\Preview\PreviewTokenStore;
use Illuminate\Http\Request;
use App\Support\Preview\PreviewContext;
use Filament\Facades\Filament;

class PreviewController extends Controller
{
    public function __construct(
        protected PreviewTokenStore $tokenStore
    ) {}

    public function shell(Request $request, string $token)
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

        $titleMap = [
            'news' => 'Pratinjau Berita',
            'page' => 'Pratinjau Halaman',
            'menu' => 'Pratinjau Navigasi',
            'settings' => 'Pratinjau Tampilan & Identitas',
        ];

        $response = response()->view('filament.preview.iframe-shell', [
            'token' => $token,
            'type' => $type,
            'title' => $titleMap[$type] ?? 'Pratinjau',
            'previewUrl' => route('admin.preview.show', ['token' => $token])
        ]);

        $response->headers->set('Cache-Control', 'no-store, private, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

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

        if (in_array($type, ['menu', 'settings'])) {
            $context = PreviewContext::fromPayload($payload, ['token' => $token]);
            app()->instance(PreviewContext::class, $context);
            $view = app(\App\Http\Controllers\PublicController::class)->index($request);
            $response = response($view);
        } else {
            $rendererClass = match ($type) {
                'news' => \App\Support\Preview\Renderers\NewsPreviewRenderer::class,
                'page' => \App\Support\Preview\Renderers\PagePreviewRenderer::class,
                'location' => \App\Support\Preview\Renderers\LocationPreviewRenderer::class,
                'document' => \App\Support\Preview\Renderers\DocumentPreviewRenderer::class,
                'gallery' => \App\Support\Preview\Renderers\GalleryAlbumPreviewRenderer::class,
                'media' => \App\Support\Preview\Renderers\MediaPreviewRenderer::class,
                default => null,
            };

            if ($rendererClass) {
                $context = PreviewContext::fromPayload($payload, ['token' => $token]);
                app()->instance(PreviewContext::class, $context);
                $renderer = new $rendererClass();
                $view = $renderer->render($context);
                $response = response($view);
            } else {
                $response = response()->view('public.preview.placeholder', [
                    'type' => htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
                ]);
            }
        }

        $response->headers->set('Cache-Control', 'no-store, private, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'", false);

        return $response;
    }
}
