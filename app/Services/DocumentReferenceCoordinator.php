<?php

namespace App\Services;

use App\Models\Document;
use App\Models\PageComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Coordinates supported builder attachment with permanent Document deletion. */
final class DocumentReferenceCoordinator
{
    public function lockReferences(array $ids): void
    {
        $ids = collect($ids)->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) === false ? null : (int) $id)
            ->filter(fn ($id) => $id !== null && $id > 0)->unique()->sort()->values()->all();
        foreach ($ids as $id) {
            $this->lock($id);
        }
        $documents = Document::withTrashed()->whereKey($ids)->lockForUpdate()->get()->keyBy('id');
        foreach ($ids as $id) {
            if (! $documents->get($id) || $documents->get($id)->trashed()) {
                throw ValidationException::withMessages(['document_ids' => 'Dokumen yang dipilih tidak tersedia.']);
            }
        }
    }

    public function lockForDeletion(int $id): void
    {
        $this->lock($id);
        Document::withTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
        $referenced = PageComponent::where('component_type', 'documents')
            ->where(function ($query) use ($id): void {
                $query->whereJsonContains('content_data->document_ids', $id)
                    ->orWhereJsonContains('content_data->document_ids', (string) $id);
            })->exists();
        if ($referenced) {
            throw ValidationException::withMessages(['document_ids' => 'Dokumen masih digunakan oleh blok halaman.']);
        }
    }

    private function lock(int $id): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Document reference coordination requires a database transaction.');
        }
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::select('select pg_advisory_xact_lock(hashtextextended(cast(? as text), 0))', ['managed-document:'.$id]);
        }
    }
}
