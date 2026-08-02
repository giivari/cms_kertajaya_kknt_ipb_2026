<?php

namespace App\Services\Preview;

use App\Models\PreviewToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class PreviewTokenStore
{
    public function __construct()
    {
        $this->validateConfig();
    }

    protected function validateConfig(): void
    {
        $ttl = config('preview.ttl_minutes');
        if (! is_int($ttl) || $ttl <= 0) {
            throw new \RuntimeException('Invalid preview.ttl_minutes configuration.');
        }

        $maxActive = config('preview.max_active');
        if (! is_int($maxActive) || $maxActive <= 0) {
            throw new \RuntimeException('Invalid preview.max_active configuration.');
        }

        $maxBytes = config('preview.max_payload_bytes');
        if (! is_int($maxBytes) || $maxBytes <= 0) {
            throw new \RuntimeException('Invalid preview.max_payload_bytes configuration.');
        }

        $supported = config('preview.supported_types');
        if (! is_array($supported) || empty($supported)) {
            throw new \RuntimeException('Invalid preview.supported_types configuration.');
        }

        foreach ($supported as $type) {
            if (! is_string($type)) {
                throw new \RuntimeException('preview.supported_types must be an array of strings.');
            }
        }
    }

    public function create(
        int|string $adminId,
        string $sessionId,
        string $previewType,
        array $payload
    ): string {
        $supportedTypes = config('preview.supported_types');
        if (! in_array($previewType, $supportedTypes, true)) {
            throw new \InvalidArgumentException("Unsupported preview type: {$previewType}");
        }

        $this->validatePayload($payload);

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadBytes = strlen($jsonPayload);
        $maxBytes = config('preview.max_payload_bytes');

        if ($payloadBytes > $maxBytes) {
            throw new \LengthException("Preview payload size exceeds maximum limit of {$maxBytes} bytes.");
        }

        $encryptedPayload = Crypt::encryptString($jsonPayload);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $sessionFingerprint = hash_hmac('sha256', $sessionId, config('app.key'));

        $now = now();
        $expiresAt = (clone $now)->addMinutes(config('preview.ttl_minutes'));

        DB::transaction(function () use ($adminId, $sessionFingerprint, $previewType, $encryptedPayload, $payloadBytes, $now, $expiresAt, $tokenHash) {
            $this->pruneExpired();

            PreviewToken::create([
                'token_hash' => $tokenHash,
                'admin_id' => $adminId,
                'session_fingerprint' => $sessionFingerprint,
                'preview_type' => $previewType,
                'encrypted_payload' => $encryptedPayload,
                'payload_bytes' => $payloadBytes,
                'created_at' => $now,
                'expires_at' => $expiresAt,
            ]);

            $maxActive = config('preview.max_active');

            $activeTokens = PreviewToken::where('admin_id', $adminId)
                ->where('session_fingerprint', $sessionFingerprint)
                ->orderBy('created_at', 'desc')
                ->pluck('id');

            if ($activeTokens->count() > $maxActive) {
                $idsToDelete = $activeTokens->slice($maxActive)->values();
                PreviewToken::whereIn('id', $idsToDelete)->delete();
            }
        });

        return $rawToken;
    }

    public function retrieve(
        string $rawToken,
        int|string $adminId,
        string $sessionId
    ): ?array {
        $tokenHash = hash('sha256', $rawToken);
        $sessionFingerprint = hash_hmac('sha256', $sessionId, config('app.key'));

        $record = PreviewToken::where('token_hash', $tokenHash)
            ->where('admin_id', $adminId)
            ->where('session_fingerprint', $sessionFingerprint)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return null;
        }

        $jsonPayload = Crypt::decryptString($record->encrypted_payload);

        return json_decode($jsonPayload, true, 512, JSON_THROW_ON_ERROR);
    }

    public function revoke(
        string $rawToken,
        int|string $adminId,
        string $sessionId
    ): bool {
        $tokenHash = hash('sha256', $rawToken);
        $sessionFingerprint = hash_hmac('sha256', $sessionId, config('app.key'));

        $deleted = PreviewToken::where('token_hash', $tokenHash)
            ->where('admin_id', $adminId)
            ->where('session_fingerprint', $sessionFingerprint)
            ->delete();

        return $deleted > 0;
    }

    public function pruneExpired(): int
    {
        return PreviewToken::where('expires_at', '<=', now())->delete();
    }

    protected function validatePayload(mixed $payload): void
    {
        if (is_object($payload)) {
            throw new \InvalidArgumentException('Objects are not allowed in preview payload.');
        }

        if (is_resource($payload)) {
            throw new \InvalidArgumentException('Resources are not allowed in preview payload.');
        }

        if (is_array($payload)) {
            foreach ($payload as $value) {
                $this->validatePayload($value);
            }
        }
    }
}
