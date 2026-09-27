<?php

namespace App\Filament\Support\Concerns;

use Filament\Actions\Action;

trait HasStatusActions
{
    protected function getFormActions(): array
    {
        $actions = [];
        
        if (method_exists($this, 'previewType')) {
            $isEditing = $this instanceof \Filament\Resources\Pages\EditRecord;
            $actions[] = \App\Filament\Support\PreviewAction::make($this->previewType(), editing: $isEditing);
        }

        $isCreate = $this instanceof \Filament\Resources\Pages\CreateRecord;
        $status = $isCreate ? 'draft' : $this->record->status;
        if (is_object($status)) {
            $status = $status->value;
        }

        if ($isCreate) {
            $actions[] = Action::make('publish')
                ->label('Terbitkan')
                ->color('primary')
                ->action(function () {
                    $this->data['status'] = 'published';
                    $this->create();
                });
            $actions[] = Action::make('draft')
                ->label('Simpan Draf')
                ->color('gray')
                ->action(function () {
                    $this->data['status'] = 'draft';
                    $this->create();
                });
        } else {
            if ($status === 'draft') {
                $actions[] = Action::make('publish')
                    ->label('Terbitkan')
                    ->color('primary')
                    ->action(function () {
                        $this->data['status'] = 'published';
                        $this->save();
                    });
                $actions[] = Action::make('save_draft')
                    ->label('Simpan Perubahan')
                    ->color('gray')
                    ->action(function () {
                        $this->data['status'] = 'draft';
                        $this->save();
                    });
            } elseif ($status === 'published') {
                $actions[] = Action::make('save')
                    ->label('Simpan Perubahan')
                    ->color('primary')
                    ->action(function () {
                        $this->data['status'] = 'published';
                        $this->save();
                    });
            } elseif ($status === 'archived') {
                 $actions[] = Action::make('publish')
                    ->label('Terbitkan Ulang')
                    ->color('primary')
                    ->action(function () {
                        $this->data['status'] = 'published';
                        $this->save();
                    });
                 $actions[] = Action::make('save')
                    ->label('Simpan Perubahan')
                    ->color('gray')
                    ->action(function () {
                        $this->data['status'] = 'archived';
                        $this->save();
                    });
            }
        }

        if (method_exists($this, 'previewType')) {
            $drafts = app(\App\Services\Preview\PreviewDraftStore::class);
            $recordId = $isCreate ? null : ($this->record->id ?? null);
            if ($drafts->restore(static::class, $recordId) !== null) {
                $actions[] = Action::make('restore_draft')
                    ->label('Pulihkan Draf Pratinjau')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () use ($recordId) {
                        $state = app(\App\Services\Preview\PreviewDraftStore::class)->restore(static::class, $recordId);
                        if ($state === null) {
                            return;
                        }
                        $this->form->fill($state);
                        \Filament\Notifications\Notification::make()->title('Draf dipulihkan!')->success()->send();
                    });
            }
        }

        $actions[] = $this->getCancelFormAction();
        return $actions;
    }
}
