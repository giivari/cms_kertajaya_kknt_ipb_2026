<?php

namespace App\Services;

use App\Enums\DerivativeType;
use App\Enums\InvisibleWatermarkStatus;
use App\Models\Media;
use App\Models\MediaDerivative;
use App\Models\WatermarkVerificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WatermarkVerificationService
{
    private const WATERMARK_VERSION = '1.0';

    /** @var list<string> */
    private const REQUIRED_PAYLOAD_KEYS = [
        'derivative_type', 'installation_id', 'issued_at', 'media_id', 'watermark_version',
    ];

    /** @var list<string> */
    private const SUPPORTED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(protected WatermarkService $watermarkService) {}

    public function generatePayload(Media $media, DerivativeType $derivativeType): array
    {
        $signingKey = config('watermark.signing_key');
        if (empty($signingKey)) {
            throw new \RuntimeException('WATERMARK_SIGNING_KEY is not configured. Processing aborted.');
        }

        $payload = [
            'installation_id' => config('village.installation_id', 'village-test'),
            'media_id' => $media->id,
            'derivative_type' => $derivativeType->value,
            'watermark_version' => self::WATERMARK_VERSION,
            'issued_at' => now()->timestamp,
        ];
        ksort($payload);
        $payload['signature'] = hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR), $signingKey);

        return $payload;
    }

    /**
     * @return array{verified: bool, metadata_authentic: bool, file_integrity: bool, format_valid: bool, legacy_unverifiable: bool, reason: string}
     */
    public function verifyDerivativeResult(MediaDerivative $derivative, Media $media): array
    {
        $result = [
            'verified' => false,
            'metadata_authentic' => false,
            'file_integrity' => false,
            'format_valid' => false,
            'legacy_unverifiable' => false,
            'reason' => 'unknown',
        ];

        $path = $this->containedDerivativePath($derivative, $media);
        if ($path === null) {
            $result['reason'] = 'missing_or_unsafe_file';

            return $this->logResult($media, $result);
        }

        $result['format_valid'] = $this->hasValidImageFormat($path, $derivative->mime_type);
        if (! $result['format_valid']) {
            $result['reason'] = 'format_invalid';

            return $this->logResult($media, $result);
        }

        if (! is_string($derivative->checksum) || ! preg_match('/^[a-f0-9]{64}$/i', $derivative->checksum)) {
            $result['legacy_unverifiable'] = true;
            $result['reason'] = 'checksum_missing';

            return $this->logResult($media, $result);
        }

        $actualChecksum = hash_file('sha256', $path);
        if ($actualChecksum === false || ! hash_equals($derivative->checksum, $actualChecksum)) {
            $result['reason'] = 'checksum_mismatch';

            return $this->logResult($media, $result);
        }
        $result['file_integrity'] = true;

        $result['metadata_authentic'] = $this->hasAuthenticPayload($derivative, $media, $path);
        if (! $result['metadata_authentic']) {
            $result['reason'] = 'metadata_invalid';

            return $this->logResult($media, $result);
        }

        $result['verified'] = true;
        $result['reason'] = 'verified';

        return $this->logResult($media, $result);
    }

    public function verifyDerivative(MediaDerivative $derivative, Media $media): bool
    {
        return $this->verifyDerivativeResult($derivative, $media)['verified'];
    }

    /**
     * @return array{verified: bool, metadata_authentic: bool, file_integrity: bool, format_valid: bool, legacy_unverifiable: bool, reason: string}
     */
    public function verifyAndPersistActiveDerivative(MediaDerivative $derivative, Media $media): array
    {
        $result = $this->verifyDerivativeResult($derivative, $media);

        DB::transaction(function () use ($derivative, $media, $result): void {
            $current = Media::query()->lockForUpdate()->findOrFail($media->getKey());
            $active = $current->derivatives()->where('derivative_type', DerivativeType::PUBLIC)->first();
            if (! $active || $active->getKey() !== $derivative->getKey()) {
                return;
            }

            if ($result['verified']) {
                $current->invisible_watermark_status = InvisibleWatermarkStatus::VERIFIED;
                $current->last_processing_status = 'verification_completed';
                $current->last_processing_error = null;
            } elseif (! $result['legacy_unverifiable']) {
                $current->invisible_watermark_status = InvisibleWatermarkStatus::FAILED;
                $current->last_processing_status = 'verification_failed';
                $current->last_processing_error = 'verification_'.$result['reason'];
            } else {
                // Historical records with no trusted derivative checksum are
                // neither silently promoted nor mass-invalidated.
                $current->last_processing_status = 'verification_unverifiable';
                $current->last_processing_error = 'verification_checksum_missing';
            }
            $current->save();
        });

        return $result;
    }

    private function hasAuthenticPayload(MediaDerivative $derivative, Media $media, string $path): bool
    {
        $signingKey = config('watermark.signing_key');
        if (! is_string($signingKey) || $signingKey === '') {
            return false;
        }

        $payload = $this->watermarkService->extractInvisibleIdentifier($path, $derivative->mime_type);
        if (! is_array($payload)) {
            return false;
        }

        $payloadKeys = array_keys($payload);
        $expectedKeys = [...self::REQUIRED_PAYLOAD_KEYS, 'signature'];
        sort($payloadKeys);
        sort($expectedKeys);
        if ($payloadKeys !== $expectedKeys) {
            return false;
        }

        $signature = $payload['signature'] ?? null;
        unset($payload['signature']);
        ksort($payload);

        if (! is_string($signature)
            || ! is_numeric($payload['issued_at'] ?? null)
            || (string) ($payload['installation_id'] ?? '') !== (string) config('village.installation_id', 'village-test')
            || (string) ($payload['media_id'] ?? '') !== (string) $media->getKey()
            || (string) ($payload['derivative_type'] ?? '') !== $derivative->derivative_type->value
            || ($payload['watermark_version'] ?? null) !== self::WATERMARK_VERSION) {
            return false;
        }

        try {
            $serializedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        $expected = hash_hmac('sha256', $serializedPayload, $signingKey);

        return hash_equals($expected, $signature);
    }

    private function hasValidImageFormat(string $path, string $expectedMime): bool
    {
        if (! in_array($expectedMime, self::SUPPORTED_IMAGE_MIMES, true)) {
            return false;
        }

        $actualMime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $dimensions = @getimagesize($path);
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if ($image !== false) {
            imagedestroy($image);
        }

        return $actualMime === $expectedMime
            && ($dimensions['mime'] ?? null) === $expectedMime
            && $image !== false;
    }

    private function containedDerivativePath(MediaDerivative $derivative, Media $media): ?string
    {
        if (! in_array($derivative->disk, ['local', 'public'], true)
            || str_contains($derivative->filename, "\0")
            || str_contains($derivative->filename, '\\')
            || str_starts_with($derivative->filename, '/')
            || preg_match('~(^|/)\.\.(/|$)~', $derivative->filename)) {
            return null;
        }

        $isCandidate = $derivative->disk === 'local'
            && str_starts_with($derivative->filename, 'staging/'.$media->getKey().'/');
        $isActive = $derivative->disk === 'local'
            && str_starts_with($derivative->filename, 'derivatives/'.$media->getKey().'/');
        $isLegacy = $derivative->disk === 'public' && str_starts_with($derivative->filename, 'media/');
        if (! $isCandidate && ! $isActive && ! $isLegacy) {
            return null;
        }

        $disk = Storage::disk($derivative->disk);
        $root = realpath($disk->path(''));
        $path = realpath($disk->path($derivative->filename));
        if ($root === false || $path === false || ! is_file($path) || ! is_readable($path) || is_link($path)) {
            return null;
        }

        return str_starts_with($path, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) ? $path : null;
    }

    /**
     * @param array{verified: bool, metadata_authentic: bool, file_integrity: bool, format_valid: bool, legacy_unverifiable: bool, reason: string} $result
     * @return array{verified: bool, metadata_authentic: bool, file_integrity: bool, format_valid: bool, legacy_unverifiable: bool, reason: string}
     */
    private function logResult(Media $media, array $result): array
    {
        WatermarkVerificationLog::create([
            'media_id' => $media->getKey(),
            'is_verified' => $result['verified'],
            'details' => $result,
        ]);

        return $result;
    }
}
