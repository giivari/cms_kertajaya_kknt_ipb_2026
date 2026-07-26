<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AuditLog::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('admin.username')
                    ->label('Pengguna'),
                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->badge(),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Tipe Entitas'),
            ])
            ->paginated(false)
            ->heading('Aktivitas Terbaru');
    }
}
