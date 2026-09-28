<?php

namespace App\Http\Controllers\Public;

use App\Enums\DerivativeType;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaDeliveryService;

final class MediaDerivativeController extends Controller
{
    public function __invoke(Media $media, MediaDeliveryService $delivery)
    {
        $variant = request()->query('variant');
        abort_unless($variant === null || $variant === 'thumbnail', 404);
        $resolved = $delivery->resolve($media, type: $variant === 'thumbnail' ? DerivativeType::THUMBNAIL : DerivativeType::PUBLIC);
        abort_unless($resolved, 404);

        $response = response()->file($resolved['path'], [
            'Content-Type' => $resolved['mime'],
            'Cache-Control' => 'no-store, max-age=0, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);

        // Symfony BinaryFileResponse marks files public after construction.
        // This controlled route is revocable, so enforce the final cache scope.
        return $response->setPrivate();
    }
}
