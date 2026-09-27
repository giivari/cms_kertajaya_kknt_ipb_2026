<?php

namespace App\Services\Preview;

use App\Models\PreviewToken;
use App\Services\MediaInputPolicy;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class PreviewTemporaryAssets
{
    /** @var array<string, array{path: string, mime: string, sha256: string}> */
    private array $assets = [];

    /** Copy only supported unsaved image uploads into a token-owned private namespace. */
    public function capture(mixed $value): mixed
    {
        if ($value instanceof TemporaryUploadedFile) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath());
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                // Document/HEIC bytes are not exposed by this image-only
                // endpoint; retain only harmless editorial metadata.
                return [
                    '__preview_upload_metadata' => true,
                    'name' => basename($value->getClientOriginalName()),
                    'mime' => is_string($mime) ? $mime : null,
                    'size' => $value->getSize(),
                ];
            }
            app(MediaInputPolicy::class)->inspect($value->getRealPath(), $value->getClientOriginalName());

            $assetId = bin2hex(random_bytes(16));
            $path = 'preview-assets/'.bin2hex(random_bytes(16)).'/'.$assetId;
            $stream = fopen($value->getRealPath(), 'rb');
            try {
                if ($stream === false || ! Storage::disk('local')->put($path, $stream)) {
                    throw new \RuntimeException('Gagal menyiapkan aset pratinjau.');
                }
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($path);
                throw $exception;
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $this->assets[$assetId] = [
                'path' => $path,
                'mime' => $mime,
                'sha256' => hash_file('sha256', Storage::disk('local')->path($path)),
            ];

            return ['__preview_asset' => $assetId, 'mime' => $mime];
        }

        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $value[$key] = $this->capture($child);
            }

            return $value;
        }

        return is_object($value) || is_resource($value) ? null : $value;
    }

    public function map(): array
    {
        return $this->assets;
    }

    public function discard(): void
    {
        Storage::disk('local')->delete(array_column($this->assets, 'path'));
    }

    public static function assetId(mixed $value): ?string
    {
        $id = is_array($value) ? ($value['__preview_asset'] ?? null) : null;

        return is_string($id) && preg_match('/^[a-f0-9]{32}$/D', $id) ? $id : null;
    }

    /** Reconcile only this service's private files, never business media. */
    public static function pruneOrphans(): int
    {
        $referenced = [];
        foreach (PreviewToken::where('expires_at', '>', now())->cursor() as $token) {
            try {
                $payload = json_decode(Crypt::decryptString($token->encrypted_payload), true, 512, JSON_THROW_ON_ERROR);
            } catch (DecryptException|\JsonException $exception) {
                return 0; // Unknown ownership: retain all files conservatively.
            }
            foreach ((array) ($payload['temporary_assets_map'] ?? []) as $asset) {
                if (is_array($asset) && is_string($asset['path'] ?? null)) {
                    $referenced[$asset['path']] = true;
                }
            }
        }

        $disk = Storage::disk('local');
        $root = realpath($disk->path('preview-assets'));
        if ($root === false || is_link($disk->path('preview-assets'))) {
            return 0;
        }

        $removed = 0;
        $cutoff = now()->subMinutes(config('preview.ttl_minutes') * 2)->getTimestamp();
        foreach ($disk->allFiles('preview-assets') as $path) {
            if (isset($referenced[$path]) || ! preg_match('~^preview-assets/[a-f0-9]{32}/[a-f0-9]{32}$~D', $path)) {
                continue;
            }
            $file = realpath($disk->path($path));
            if ($file === false || ! $disk->exists($path)
                || ! str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)
                || is_link($disk->path($path)) || is_link(dirname($disk->path($path)))
                || $disk->lastModified($path) > $cutoff) {
                continue;
            }
            $removed += $disk->delete($path) ? 1 : 0;
        }

        return $removed;
    }
}
