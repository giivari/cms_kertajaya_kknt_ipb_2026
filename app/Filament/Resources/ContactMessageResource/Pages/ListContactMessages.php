<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getTabs(): array
    {
        return [
            'inbox' => Tab::make('Inbox')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('archived_at')),
            'archived' => Tab::make('Diarsipkan')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('archived_at')),
            'all' => Tab::make('Semua')
                ->modifyQueryUsing(fn (Builder $query) => $query),
        ];
    }
}