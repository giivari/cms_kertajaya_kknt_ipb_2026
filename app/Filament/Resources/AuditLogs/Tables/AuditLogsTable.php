<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Filament\Exports\AuditLogExporter;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Support\AdminTable;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table admin-audit-log-table')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s', 'Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('admin.name')
                    ->label('Pelaku')
                    ->placeholder('Sistem')
                    ->searchable()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('event_type')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diubah',
                        'deleted' => 'Dihapus',
                        'restored' => 'Dipulihkan',
                        'force_deleted' => 'Dihapus Permanen',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted', 'force_deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('subject_type')
                    ->label('Target')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),

                // Kolom Mobile (Layar Kecil - Digabungkan)
                TextColumn::make('admin_name_mobile')
                    ->label('Pelaku & Waktu')
                    ->state(fn (\App\Models\AuditLog $record) => $record->admin?->name)
                    ->placeholder('Sistem')
                    ->description(fn (\App\Models\AuditLog $record): string => $record->created_at->format('d/m/y H.i'))
                    ->hiddenFrom('md'),
                TextColumn::make('event_type_mobile')
                    ->label('Aksi & Target')
                    ->state(fn (\App\Models\AuditLog $record) => $record->event_type)
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diubah',
                        'deleted' => 'Dihapus',
                        'restored' => 'Dipulihkan',
                        'force_deleted' => 'Dihapus Permanen',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted', 'force_deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    })
                    ->description(fn (\App\Models\AuditLog $record): string => class_basename($record->subject_type))
                    ->hiddenFrom('md'),

                // Kolom Opsional Lainnya
                TextColumn::make('subject_id')
                    ->label('ID Target')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->searchable()
                    ->visibleFrom('lg'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make()->label('Lihat')->icon('heroicon-o-eye'),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Aksi'),
            ])
            ->toolbarActions([
                AdminTable::exportAction(AuditLogExporter::class, AuditLogResource::class),
            ]);
    }
}
