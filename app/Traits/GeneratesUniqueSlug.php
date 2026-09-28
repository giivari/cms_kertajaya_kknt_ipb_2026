<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    private const SLUG_CONFLICT_RETRY_LIMIT = 3;

    public static function generateUniqueSlug(?string $source, ?int $exceptId = null): string
    {
        $baseSlug = Str::slug((string) $source);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'item';
        $slug = $baseSlug;
        $suffix = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * The preliminary exists() check above improves the ordinary authoring path,
     * but PostgreSQL's unique index remains the final concurrency boundary. A
     * concurrent writer can commit between that check and this insert.
     *
     * @param  Builder<static>  $query
     */
    protected function performInsert(Builder $query)
    {
        return $this->performWithSlugConflictRetry(fn () => parent::performInsert($query));
    }

    /**
     * @param  Builder<static>  $query
     */
    protected function performUpdate(Builder $query)
    {
        return $this->performWithSlugConflictRetry(fn () => parent::performUpdate($query));
    }

    private function performWithSlugConflictRetry(\Closure $operation): bool
    {
        // A failed PostgreSQL statement aborts its current transaction. Execute
        // every write inside a Laravel transaction so an outer transaction uses
        // a savepoint and an un-nested write uses its own transaction. The
        // exception must leave this callback for Laravel to roll it back first.
        for ($attempt = 0; $attempt <= self::SLUG_CONFLICT_RETRY_LIMIT; $attempt++) {
            try {
                return $this->getConnection()->transaction($operation);
            } catch (QueryException $exception) {
                if (! $this->isSlugUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                if ($attempt === self::SLUG_CONFLICT_RETRY_LIMIT) {
                    throw $exception;
                }

                $this->slug = static::generateUniqueSlug(
                    $this->getAttribute('title') ?? $this->getAttribute('name') ?? $this->slug,
                    $this->exists ? $this->getKey() : null,
                );
            }
        }
    }

    private function isSlugUniqueConstraintViolation(QueryException $exception): bool
    {
        if ((string) ($exception->errorInfo[0] ?? $exception->getCode()) !== '23505') {
            return false;
        }

        $constraint = sprintf('"%s_slug_unique"', $this->getTable());
        $message = (string) ($exception->errorInfo[2] ?? $exception->getMessage());

        return str_contains($message, $constraint);
    }
}
