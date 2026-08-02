<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Validator;

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
            ->label($editing ? 'Pratinjau Perubahan' : 'Pratinjau')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->visible(fn (): bool => config('preview.ui_enabled', false))
            ->modalHeading($editing ? 'Pratinjau Perubahan' : 'Pratinjau')
            ->modalWidth($type === 'news' ? Width::Screen : Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->form(function () use ($type, $editing) {
                if ($type !== 'news') {
                    return [];
                }
                
                return [
                    \Filament\Forms\Components\Hidden::make('preview_token')
                        ->default(function ($livewire, \App\Services\Preview\PreviewTokenStore $store) use ($editing, $type) {
                            $state = $livewire->form->getRawState();
                            $state = $state instanceof Arrayable ? $state->toArray() : $state;
                            $normalizedState = PreviewStateNormalizer::normalize($type, $state);
                            
                            $admin = \Filament\Facades\Filament::auth()->user();
                            $sessionId = request()->hasSession() ? request()->session()->getId() : 'test-session';

                            $recordSnapshot = null;
                            if ($editing && isset($livewire->record)) {
                                $recordSnapshot = $livewire->record->only([
                                    'id', 'title', 'slug', 'excerpt', 'content', 'status', 'published_at',
                                    'news_category_id', 'featured_media_id', 'seo_title', 'seo_description'
                                ]);
                            }

                            $payload = [
                                'version' => 1,
                                'type' => 'news',
                                'mode' => $editing ? 'edit' : 'create',
                                'record_id' => $editing ? ($livewire->record->id ?? null) : null,
                                'state' => $normalizedState,
                                'snapshot' => $recordSnapshot,
                            ];

                            return $store->create($admin->id, $sessionId, 'news', $payload);
                        })
                ];
            })
            ->mountUsing(function ($livewire) use ($rules): void {
                $state = $livewire->form->getRawState();
                $state = $state instanceof Arrayable ? $state->toArray() : $state;

                Validator::make($state, $rules, [
                    'required' => 'Lengkapi field ini untuk membuka pratinjau.',
                ])->validate();
            })
            ->modalContent(function (\Filament\Actions\Action $action, $livewire) use ($type, $editing) {
                $state = $livewire->form->getRawState();
                $state = $state instanceof Arrayable ? $state->toArray() : $state;
                $normalizedState = PreviewStateNormalizer::normalize($type, $state);

                if ($type === 'news') {
                    $token = $action->getFormData()['preview_token'] ?? null;
                    $previewUrl = $token ? route('admin.preview.show', ['token' => $token]) : '#';
                    return view('filament.preview.iframe-shell', [
                        'previewUrl' => $previewUrl,
                        'title' => 'Pratinjau Berita',
                    ]);
                }

                return view('filament.preview.content', [
                    'type' => $type,
                    'state' => $normalizedState,
                ]);
            });
    }
}






