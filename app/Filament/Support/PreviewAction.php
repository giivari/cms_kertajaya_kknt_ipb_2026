<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Validator;
use App\Services\Preview\PreviewTokenStore;
use Filament\Facades\Filament;

class PreviewAction
{
    public static function make(string $type, bool $editing = false): Action
    {
        $rules = match ($type) {
            'news', 'page', 'gallery', 'document' => ['title' => ['required', 'string']],
            'location' => ['name' => ['required', 'string'], 'location_category_id' => ['required']],
            'menu' => ['location' => ['required']],
            'location-category', 'news-category', 'document-category' => ['name' => ['required', 'string']],
            'media' => $editing ? [] : ['file' => ['required']],
            'settings' => ['village_name' => ['required', 'string']],
            default => [],
        };

        return Action::make('preview')
            ->label('Pratinjau')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->extraAttributes([
                'x-on:click' => "window.open('', 'preview_tab')"
            ])
            ->visible(fn (): bool => config('preview.ui_enabled', false))
            ->action(function ($livewire) use ($type, $editing, $rules) {
                $state = $livewire->form->getRawState();
                $state = $state instanceof Arrayable ? $state->toArray() : $state;

                Validator::make($state, $rules, [
                    'required' => 'Lengkapi field ini untuk membuka pratinjau.',
                ])->validate();

                $normalizedState = PreviewStateNormalizer::normalize($type, $state);

                $admin = Filament::auth()->user();
                $sessionId = request()->hasSession() ? request()->session()->getId() : 'test-session';

                $recordSnapshot = null;
                if ($editing && isset($livewire->record)) {
                    $recordSnapshot = $livewire->record->getAttributes();
                }

                $payload = [
                    'version' => 1,
                    'type' => $type,
                    'mode' => $editing ? 'edit' : 'create',
                    'record_id' => $editing ? ($livewire->record->id ?? null) : null,
                    'state' => $normalizedState,
                    'snapshot' => $recordSnapshot,
                ];

                $store = app(\App\Services\Preview\PreviewTokenStore::class);
                $token = $store->create($admin->id, $sessionId, $type, $payload);
                $url = route('admin.preview.shell', ['token' => $token]);

                if (request()->hasSession()) {
                    $cacheKey = 'preview_draft_' . get_class($livewire) . '_' . ($editing ? ($livewire->record->id ?? 'new') : 'new');
                    request()->session()->put($cacheKey, $state);
                }

                if (app()->environment('testing')) {
                    return redirect($url);
                }

                $livewire->js("window.open('{$url}', 'preview_tab')");
            });
    }
}






