<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\MediaInputPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaOriginalController extends Controller
{
    public function __invoke(Media $media)
    {
        // The route also enforces the P1A session/MFA/password middleware stack.
        Gate::authorize('update', $media);
        abort_if($media->trashed(), 404);

        try {
            if ($media->disk === 'local' && $media->directory === 'originals') {
                $path = app(MediaInputPolicy::class)->originalPath('originals/'.$media->filename);
            } elseif ($media->disk === 'public'
                && in_array(trim($media->directory, '/'), ['media', 'originals'], true)
                && basename($media->filename) === $media->filename) {
                $disk = Storage::disk('public');
                $root = realpath($disk->path(''));
                $path = realpath($disk->path(trim($media->directory, '/').'/'.$media->filename));
                abort_unless($root !== false && $path !== false && is_file($path) && is_readable($path)
                    && str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR), 404);
            } else {
                abort(404);
            }
        } catch (ValidationException) {
            abort(404);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        abort_unless(isset(MediaInputPolicy::EXTENSIONS[$mime]), 404);
        $headers = [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ];

        $response = in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/heic'], true)
            ? response()->file($path, $headers)
            : response()->download($path, 'original.'.$media->extension, $headers);

        // BinaryFileResponse defaults to public; originals must remain private.
        return $response->setPrivate();
    }
}
