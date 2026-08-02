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
                TextColumn::make('id')
                    ->label('ID')
                    ->visibleFrom('lg'),
                TextColumn::make('admin.name')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->label('Jenis Kejadian')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Jenis Data')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('subject_id')
                    ->label('ID Data')
                    ->searchable()
                    ->visibleFrom('lg'),
                TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->label('Lihat'),
            ])
            ->toolbarActions([
                AdminTable::exportAction(AuditLogExporter::class, AuditLogResource::class),
            ]);
    }
}
