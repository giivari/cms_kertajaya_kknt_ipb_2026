<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\DocumentCategory;
use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Applies the category-management modal as an explicit delta.
 *
 * The modal carries only the category IDs that existed when it was opened.
 * That snapshot prevents a stale tab from deleting a category created later
 * by another request.
 */
final class CategoryMutationService
{
    /** @param array<int, array{id?: int|string|null, name?: mixed}> $categories */
    /** @param array<int, int|string> $originalIds */
    /** @param array<int|string, string> $originalVersions */
    public function syncNews(Admin $admin, array $categories, array $originalIds, array $originalVersions): void
    {
        $this->sync($admin, NewsCategory::class, $categories, $originalIds, $originalVersions);
    }

    /** @param array<int, array{id?: int|string|null, name?: mixed}> $categories */
    /** @param array<int, int|string> $originalIds */
    /** @param array<int|string, string> $originalVersions */
    public function syncDocuments(Admin $admin, array $categories, array $originalIds, array $originalVersions): void
    {
        $this->sync($admin, DocumentCategory::class, $categories, $originalIds, $originalVersions);
    }

    /**
     * @param class-string<Model> $categoryClass
     * @param array<int, array{id?: int|string|null, name?: mixed}> $categories
     * @param array<int, int|string> $originalIds
     * @param array<int|string, string> $originalVersions
     */
    private function sync(Admin $admin, string $categoryClass, array $categories, array $originalIds, array $originalVersions): void
    {
        Gate::forUser($admin)->authorize('create', $categoryClass);

        $originalIds = collect($originalIds)
            ->filter(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($admin, $categoryClass, $categories, $originalIds, $originalVersions): void {
            $submittedIds = [];

            foreach ($categories as $categoryData) {
                $id = $categoryData['id'] ?? null;
                $name = $this->normalizedName($categoryData['name'] ?? null);

                if ($id === null || $id === '') {
                    if ($name !== null) {
                        $category = new $categoryClass(['name' => $name]);
                        Gate::forUser($admin)->authorize('create', $categoryClass);
                        $category->save();
                    }

                    continue;
                }

                if (filter_var($id, FILTER_VALIDATE_INT) === false || ! in_array((int) $id, $originalIds, true)) {
                    throw ValidationException::withMessages([
                        'categories' => 'Daftar kategori telah berubah. Muat ulang formulir sebelum menyimpan perubahan.',
                    ]);
                }

                /** @var Model|null $category */
                $category = $categoryClass::query()->lockForUpdate()->find((int) $id);
                if (! $category) {
                    throw ValidationException::withMessages([
                        'categories' => 'Kategori yang Anda ubah sudah tidak tersedia. Muat ulang formulir sebelum melanjutkan.',
                    ]);
                }

                $this->assertUnchanged($category, $originalVersions[(string) $id] ?? null);

                Gate::forUser($admin)->authorize('update', $category);
                $submittedIds[] = (int) $id;

                if ($name === null) {
                    throw ValidationException::withMessages([
                        'categories' => 'Nama kategori tidak boleh kosong. Hapus baris kategori untuk menghapusnya.',
                    ]);
                }

                $category->name = $name;
                $category->save();
            }

            foreach (array_values(array_diff($originalIds, $submittedIds)) as $id) {
                /** @var Model|null $category */
                $category = $categoryClass::query()->lockForUpdate()->find($id);
                if (! $category) {
                    continue;
                }

                $this->assertUnchanged($category, $originalVersions[(string) $id] ?? null);

                Gate::forUser($admin)->authorize('delete', $category);
                $category->delete();
            }
        }, attempts: 3);
    }

    private function normalizedName(mixed $name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        $name = trim($name);
        if ($name === '') {
            return null;
        }

        if (mb_strlen($name) > 150) {
            throw ValidationException::withMessages([
                'categories' => 'Nama kategori maksimal 150 karakter.',
            ]);
        }

        return $name;
    }

    private function assertUnchanged(Model $category, mixed $expectedVersion): void
    {
        $currentVersion = $category->updated_at?->format('Y-m-d\\TH:i:s.uP');

        if (! is_string($expectedVersion) || ! hash_equals($expectedVersion, (string) $currentVersion)) {
            throw ValidationException::withMessages([
                'categories' => 'Kategori telah diubah pada sesi lain. Muat ulang formulir sebelum menyimpan perubahan.',
            ]);
        }
    }
}
