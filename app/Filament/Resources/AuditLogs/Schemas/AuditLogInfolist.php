<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Log')->schema([
                    TextEntry::make('id')->label('ID Log'),
                    TextEntry::make('admin.name')->label('Admin/Pelaku')->placeholder('Sistem'),
                    TextEntry::make('event_type')->label('Jenis Kejadian')
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
                        }),
                    TextEntry::make('created_at')->label('Waktu Kejadian')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta'),
                ])->columns(2),
                \Filament\Schemas\Components\Section::make('Konteks Data')->schema([
                    TextEntry::make('subject_type')->label('Model/Jenis Data')->placeholder('-'),
                    TextEntry::make('subject_id')->label('ID Data')->placeholder('-'),
                ])->columns(2),
                \Filament\Schemas\Components\Section::make('Informasi Klien')->schema([
                    TextEntry::make('ip_address')->label('Alamat IP')->placeholder('-'),
                    TextEntry::make('user_agent')->label('Peramban (User Agent)')->placeholder('-')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
