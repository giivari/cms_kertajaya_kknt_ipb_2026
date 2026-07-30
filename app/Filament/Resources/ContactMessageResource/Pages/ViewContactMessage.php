<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_status')
                ->label('Ubah Status')
                ->icon('heroicon-o-tag')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(ContactStatus::class)
                        ->required()
                        ->default(fn ($record) => $record->status->value),
                ])
                ->action(function (array $data, $record): void {
                    $record->changeStatus($data['status'] instanceof ContactStatus ? $data['status'] : ContactStatus::from($data['status']));
                }),
            Actions\Action::make('mark_read')
                ->label('Tandai Dibaca')
                ->icon('heroicon-o-check-circle')
                ->visible(fn ($record) => $record->status === ContactStatus::NEW)
                ->action(fn ($record) => $record->markAsRead()),
            Actions\Action::make('archive')
                ->label('Arsipkan')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->visible(fn ($record) => is_null($record->archived_at))
                ->action(fn ($record) => $record->archive()),
            Actions\Action::make('restore')
                ->label('Pulihkan')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn ($record) => !is_null($record->archived_at))
                ->action(fn ($record) => $record->restoreFromArchive()),
        ];
    }
}