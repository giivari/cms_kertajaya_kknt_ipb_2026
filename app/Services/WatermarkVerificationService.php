<?php

namespace App\Services;

use App\Enums\DerivativeType;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Models\WatermarkVerificationLog;
use Illuminate\Support\Facades\Storage;

class WatermarkVerificationService
{
    public function __construct(protected WatermarkService $watermarkService) {}

    public function generatePayload(Media $media, DerivativeType $derivativeType): array
    {
        $signingKey = config('watermark.signing_key');
        if (empty($signingKey)) {
            throw new \Exception('WATERMARK_SIGNING_KEY is not configured. Processing aborted.');
        }

        $payload = [
            'installation_id' => config('village.installation_id', 'village-test'),
            'media_id' => $media->id,
            'derivative_type' => $derivativeType->value,
            'watermark_version' => '1.0',
            'issued_at' => now()->timestamp,
        ];

        // Ensure fixed ordering
        ksort($payload);

        $signature = hash_hmac('sha256', json_encode($payload), $signingKey);

        $payload['signature'] = $signature;

        return $payload;
    }

    public function verifyDerivative(MediaDerivative $derivative, Media $media): bool
    {
        $signingKey = config('watermark.signing_key');
        if (empty($signingKey)) {
            // Processing fails closed if key is missing
            $this->logVerification($media->id, false, 'Missing WATERMARK_SIGNING_KEY.');

            return false;
        }

        $path = Storage::disk($derivative->disk)->path($derivative->filename);
        $extracted = $this->watermarkService->extractInvisibleIdentifier($path, $derivative->mime_type);

        $isVerified = false;
        $details = null;

        if ($extracted && is_array($extracted)) {
            $signature = $extracted['signature'] ?? null;
            unset($extracted['signature']);

            // Reconstruct payload without signature and sort exactly like generation
            ksort($extracted);

            $expectedSignature = hash_hmac('sha256', json_encode($extracted), $signingKey);

            if (! hash_equals($expectedSignature, (string) $signature)) {
                $details = 'Watermark signature invalid or payload tampered.';
            } elseif (($extracted['media_id'] ?? null) != $media->id) {
                $details = 'Watermark media_id mismatch.';
            } elseif (($extracted['derivative_type'] ?? null) != $derivative->derivative_type->value) {
                $details = 'Watermark derivative_type mismatch.';
            } else {
                $isVerified = true;
                $details = 'Watermark verified successfully.';
            }
        } else {
            $details = 'Watermark extraction failed, payload missing, or malformed.';
        }

        $this->logVerification($media->id, $isVerified, $details);

        return $isVerified;
    }

    protected function logVerification(string $mediaId, bool $isVerified, ?string $details): void
    {
        WatermarkVerificationLog::create([
            'media_id' => $mediaId,
            'is_verified' => $isVerified,
            'details' => $details,
        ]);
    }
}
