<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreviewToken;
use App\Services\Preview\PreviewTokenStore;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        if (!isset($assetsMap[$assetToken])) {
            abort(404);
        }

        $tempPath = $assetsMap[$assetToken];
        // Ensure path doesn't contain directory traversal
        if (str_contains($tempPath, '..')) {
            abort(403);
        }

        $disk = Storage::disk(config('filament.default_filesystem_disk')); // Or livewire tmp disk? Let's assume default for Filament uploads, though Filament usually uses local or public.
        // Actually livewire temp uses local disk typically, but Filament file upload may move it or keep it.
        // If it's a livewire temporary upload, it is on the local disk. Let's just use Storage::disk('local') since livewire tmp is there.
        // Wait, filament uses its own temp disk sometimes. Let's try Storage::disk('local') or Storage::disk(config('livewire.temporary_file_upload.disk') ?: 'local')
        $diskName = config('livewire.temporary_file_upload.disk') ?: 'local';
        $disk = Storage::disk($diskName);

        if (!$disk->exists($tempPath)) {
            abort(404);
        }

        $mimeType = $disk->mimeType($tempPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        
        if (!in_array($mimeType, $allowedMimes)) {
            abort(403, 'Tipe file tidak diizinkan untuk pratinjau.');
        }

        $response = response()->file($disk->path($tempPath), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->headers->set('Cache-Control', 'no-store, private, no-cache, must-revalidate');
        
        return $response;
    }
}
