<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaInputPolicy;
use App\Services\Preview\PreviewTokenStore;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PreviewAssetController extends Controller
{
    public function __construct(
        protected PreviewTokenStore $tokenStore
    ) {}

    public function show(Request $request, string $token, string $assetToken)
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

        $assetsMap = $payload['temporary_assets_map'] ?? [];
        if (! is_array($assetsMap) || ! preg_match('/^[a-f0-9]{32}$/D', $assetToken) || !isset($assetsMap[$assetToken])) {
            abort(404);
        }

        $asset = $assetsMap[$assetToken];
        $path = is_array($asset) ? ($asset['path'] ?? null) : null;
        abort_unless(is_string($path) && preg_match('~^preview-assets/[a-f0-9]{32}/'.preg_quote($assetToken, '~').'$~D', $path), 404);

        $disk = Storage::disk('local');
        $root = realpath($disk->path('preview-assets'));
        $resolved = realpath($disk->path($path));
        abort_unless($root !== false && $resolved !== false && is_file($resolved)
            && str_starts_with($resolved, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
            && ! is_link($disk->path('preview-assets'))
            && ! is_link($disk->path($path)) && ! is_link(dirname($disk->path($path))), 404);

        try {
            $mime = app(MediaInputPolicy::class)->inspect($resolved);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            abort(404);
        }
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
            && hash_equals((string) ($asset['mime'] ?? ''), $mime)
            && hash_equals((string) ($asset['sha256'] ?? ''), hash_file('sha256', $resolved)), 404);

        $response = response()->file($resolved, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);

        return $response->setPrivate();
    }
}
