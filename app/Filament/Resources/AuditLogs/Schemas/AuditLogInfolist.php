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
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('admin.name')
                    ->label('Admin')
                    ->placeholder('-'),
                TextEntry::make('event_type')
                    ->label('Jenis Kejadian'),
                TextEntry::make('subject_type')
                    ->label('Jenis Data')
                    ->placeholder('-'),
                TextEntry::make('subject_id')
                    ->label('ID Data')
                    ->placeholder('-'),
                TextEntry::make('ip_address')
                    ->label('Alamat IP')
                    ->placeholder('-'),
                TextEntry::make('user_agent')
                    ->label('Peramban')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta'),
            ]);
    }
}
