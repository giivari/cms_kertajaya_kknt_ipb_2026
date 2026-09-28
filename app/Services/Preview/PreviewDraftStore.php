<?php

namespace App\Services\Preview;

use Illuminate\Contracts\Support\Arrayable;
use Filament\Facades\Filament;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class PreviewDraftStore
{
    public function key(string $editor, int|string|null $recordId): string
    {
        return 'preview_draft_'.hash('sha256', $editor.'|'.($recordId ?? 'new'));
    }

    public function remember(string $editor, int|string|null $recordId, array $state): void
    {
        $state = $this->safeState($state);
        $encoded = json_encode($state, JSON_THROW_ON_ERROR);
        if (strlen($encoded) > config('preview.max_payload_bytes')) {
            return;
        }

        session()->put($this->key($editor, $recordId), [
            'admin_id' => Filament::auth()->id(),
            'expires_at' => now()->addMinutes(config('preview.ttl_minutes'))->getTimestamp(),
            'state' => $state,
        ]);
    }

    public function restore(string $editor, int|string|null $recordId): ?array
    {
        $key = $this->key($editor, $recordId);
        $draft = session()->get($key);
        if (! is_array($draft) || ! is_array($draft['state'] ?? null)
            || ($draft['admin_id'] ?? null) !== Filament::auth()->id()
            || ! is_int($draft['expires_at'] ?? null) || $draft['expires_at'] <= now()->getTimestamp()) {
            session()->forget($key);

            return null;
        }

        return $draft['state'];
    }

    public function forget(string $editor, int|string|null $recordId): void
    {
        session()->forget($this->key($editor, $recordId));
    }

    private function safeState(mixed $value): mixed
    {
        if ($value instanceof TemporaryUploadedFile || is_resource($value)) {
            return null;
        }
        if ($value instanceof Arrayable) {
            return $this->safeState($value->toArray());
        }
        if (is_array($value)) {
            $safe = [];
            foreach ($value as $key => $child) {
                if (is_string($key) && preg_match('/(?:password|secret|token|api_key|private_key)/i', $key)) {
                    continue;
                }
                $safe[$key] = $this->safeState($child);
            }

            return $safe;
        }

        return is_object($value) ? null : $value;
    }
}
