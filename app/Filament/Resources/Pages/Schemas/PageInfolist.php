<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Halaman')->schema([
                TextEntry::make('title')->label('Judul'),
                TextEntry::make('excerpt')->label('Ringkasan')->placeholder('-')->columnSpanFull(),
                TextEntry::make('status')->label('Status')->badge()->formatStateUsing(function ($state): string {
                    $value = $state instanceof PageStatus ? $state->value : $state;

                    return match ($value) {
                        'published' => 'Terbit', 'archived' => 'Diarsipkan', default => 'Draf',
                    };
                }),
                TextEntry::make('published_at')->label('Diterbitkan pada')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')->placeholder('-'),
            ])->columns(2),
        ]);
    }
}
