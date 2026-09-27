<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Validator;
use App\Services\Preview\PreviewTokenStore;
use Filament\Facades\Filament;
use App\Services\Preview\PreviewDraftStore;
use App\Services\Preview\PreviewTemporaryAssets;

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
                $admin = Filament::auth()->user();
                $sessionId = session()->getId();
                abort_unless($admin && is_string($sessionId) && $sessionId !== '', 403);
                $recordId = $editing ? ($livewire->record->id ?? null) : null;
                if ($type === 'menu' && $editing && ! array_key_exists('location', $state)) {
                    $state['location'] = $livewire->record->location ?? null;
                }
                app(PreviewDraftStore::class)->remember(get_class($livewire), $recordId, $state);

                Validator::make($state, $rules, [
                    'required' => 'Lengkapi field ini untuk membuka pratinjau.',
                ])->validate();

                $assets = new PreviewTemporaryAssets();
                try {
                    $normalizedState = PreviewStateNormalizer::normalize($type, $assets->capture($state));

                    $recordSnapshot = null;
                    if ($editing && isset($livewire->record)) {
                        $recordSnapshot = $livewire->record->getAttributes();
                    }

                    $payload = [
                        'version' => 1,
                        'type' => $type,
                        'mode' => $editing ? 'edit' : 'create',
                        'record_id' => $recordId,
                        'state' => $normalizedState,
                        'snapshot' => $recordSnapshot,
                        'temporary_assets_map' => $assets->map(),
                    ];

                    $store = app(PreviewTokenStore::class);
                    $token = $store->create($admin->id, $sessionId, $type, $payload);
                } catch (\Throwable $exception) {
                    $assets->discard();
                    throw $exception;
                }
                $url = route('admin.preview.shell', ['token' => $token]);

                if (app()->environment('testing')) {
                    return redirect($url);
                }

                $livewire->js("window.open('{$url}', 'preview_tab')");
            });
    }
}



