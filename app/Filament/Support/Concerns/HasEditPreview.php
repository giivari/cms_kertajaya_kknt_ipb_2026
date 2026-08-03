<?php

namespace App\Filament\Support\Concerns;

use App\Filament\Support\PreviewAction;

trait HasEditPreview
{
    abstract protected function previewType(): string;

    protected function getFormActions(): array
    {
        $actions = [
            \App\Filament\Support\PreviewAction::make($this->previewType(), editing: true),
            $this->getSaveFormAction()->label('Simpan Perubahan'),
            $this->getCancelFormAction()->label('Batal'),
        ];

        $cacheKey = 'preview_draft_' . static::class . '_' . ($this->record->id ?? 'new');
        if (session()->has($cacheKey)) {
            $actions[] = \Filament\Actions\Action::make('restore_draft')
                ->label('Pulihkan Draf Pratinjau')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->action(function () use ($cacheKey) {
                    $this->form->fill(session()->get($cacheKey));
                    \Filament\Notifications\Notification::make()->title('Draf dipulihkan!')->success()->send();
                });
        }

        return $actions;
    }

    protected function afterSave(): void
    {
        $cacheKey = 'preview_draft_' . static::class . '_' . ($this->record->id ?? 'new');
        if (session()->has($cacheKey)) {
            session()->forget($cacheKey);
        }
    }
}

