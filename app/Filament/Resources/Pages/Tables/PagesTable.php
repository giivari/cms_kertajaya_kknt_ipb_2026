<?php

namespace App\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                \Filament\Tables\Actions\ActionGroup::make([
                    EditAction::make()->label('Ubah')->icon('heroicon-o-pencil-square'),
                    \Filament\Tables\Actions\Action::make('archive')
                        ->label('Arsipkan')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->visible(fn ($record) => (is_object($record->status) ? $record->status->value : $record->status) === 'published')
                        ->action(fn ($record) => $record->update(['status' => 'archived']))
                        ->requiresConfirmation()
                        ->modalHeading('Arsipkan Halaman')
                        ->modalDescription('Apakah Anda yakin ingin mengarsipkan halaman ini?')
                        ->modalSubmitActionLabel('Arsipkan'),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
