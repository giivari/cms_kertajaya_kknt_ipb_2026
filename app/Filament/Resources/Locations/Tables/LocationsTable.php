<?php

namespace App\Filament\Resources\Locations\Tables;

use App\Filament\Exports\LocationExporter;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Support\AdminTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-location-table')
            ->recordUrl(fn ($record): string => LocationResource::getUrl('view', ['record' => $record]))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->limit(40),
                TextColumn::make('category.name')->label('Kategori')->searchable()->sortable()->visibleFrom('md'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Terbit', 'archived' => 'Diarsipkan', default => 'Draf',
                    })
                    ->color(fn (string $state): string => match ($state) {
                    'published' => 'success',
                    'archived' => 'warning',
                    default => 'gray',
                }),
                TextColumn::make('published_at')->label('Diterbitkan pada')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')->sortable()->visibleFrom('md'),
                TextColumn::make('sort_order')->label('Urutan')->sortable()->visibleFrom('lg'),
                TextColumn::make('updated_at')->label('Terakhir Diperbarui')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')->sortable()->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make()->label('Lihat')->icon('heroicon-o-eye'),
                    EditAction::make()->label('Ubah')->icon('heroicon-o-pencil-square'),
                    \Filament\Tables\Actions\Action::make('archive')
                        ->label('Arsipkan')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->visible(fn ($record) => (is_object($record->status) ? $record->status->value : $record->status) === 'published')
                        ->action(fn ($record) => $record->update(['status' => 'archived']))
                        ->requiresConfirmation()
                        ->modalHeading('Arsipkan Lokasi')
                        ->modalDescription('Apakah Anda yakin ingin mengarsipkan lokasi ini?')
                        ->modalSubmitActionLabel('Arsipkan'),
                    \Filament\Actions\DeleteAction::make()->label('Hapus')->icon('heroicon-o-trash'),
                    \Filament\Actions\ForceDeleteAction::make()->label('Hapus Permanen')->icon('heroicon-o-x-circle'),
                    \Filament\Actions\RestoreAction::make()->label('Pulihkan')->icon('heroicon-o-arrow-uturn-left'),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                AdminTable::exportAction(LocationExporter::class, LocationResource::class),
            ]);
    }
}
