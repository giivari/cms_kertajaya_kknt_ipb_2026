<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MediaReferenceCoordinator
{
    public const SETTING_KEYS = [
        'village_logo', 'favicon', 'watermark_image', 'hero_image',
        'profil_image_1', 'profil_image_2', 'potensi_1_image', 'potensi_2_image',
        'potensi_3_image', 'profil_bg_image', 'potensi_bg_image', 'stat_bg_image', 'cta_bg_image',
    ];

    /** @param array<int|string|null> $ids */
    public function lockReferences(array $ids): void
    {
        $ids = collect($ids)->filter(fn ($id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)->unique()->sort()->values()->all();

        foreach ($ids as $id) {
            $this->advisoryLock($id);
        }

        $media = Media::withTrashed()->whereKey($ids)->lockForUpdate()->get()->keyBy('id');
        foreach ($ids as $id) {
            $item = $media->get($id);
            if (! $item || $item->trashed() || in_array($item->cleanup_status, ['pending', 'failed', 'completed'], true)) {
                throw ValidationException::withMessages(['media' => 'Media yang dipilih tidak lagi tersedia.']);
            }
        }
    }

    public function lockForDeletion(int $mediaId): Media
    {
        $this->advisoryLock($mediaId);

        return Media::withTrashed()->lockForUpdate()->findOrFail($mediaId);
    }

    private function advisoryLock(int $mediaId): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Media reference coordination requires a database transaction.');
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::select('select pg_advisory_xact_lock(hashtextextended(cast(? as text), 0))', ['managed-media:'.$mediaId]);
        }
    }
}
