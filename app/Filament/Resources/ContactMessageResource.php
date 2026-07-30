<?php

namespace App\Filament\Resources;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationLabel = 'Pesan Masuk';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Pesan Kontak';
    protected static ?string $pluralModelLabel = 'Pesan Kontak';

    public static function getNavigationGroup(): ?string
    {
        return 'UTAMA';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pengirim')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama'),
                        TextEntry::make('contact_type')
                            ->label('Tipe Kontak')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'email' => 'Email',
                                'phone' => 'Telepon/WhatsApp',
                                default => $state,
                            }),
                        TextEntry::make('contact_value')
                            ->label('Kontak'),
                        TextEntry::make('subject')
                            ->label('Subjek'),
                    ])->columns(2),
                View::make('filament.schemas.components.contact-message-card')
                    ->columnSpan(1),
                Section::make('Status')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status Penanganan')
                            ->badge(),
                        TextEntry::make('read_at')
                            ->label('Dibaca Pada')
                            ->dateTime('d M Y, H.i'),
                        TextEntry::make('archived_at')
                            ->label('Diarsipkan Pada')
                            ->dateTime('d M Y, H.i'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'email' => 'Email',
                        'phone' => 'Telepon/WhatsApp',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'email' => 'primary',
                        'phone' => 'success',
                        default => 'gray',
                    })
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('contact_value')
                    ->label('Kontak')
                    ->formatStateUsing(function (string $state) {
                        if (str_contains($state, '@')) {
                            $parts = explode('@', $state);
                            if (strlen($parts[0]) > 2) {
                                return substr($parts[0], 0, 1) . '***@' . $parts[1];
                            }
                            return '***@' . $parts[1];
                        }
                        if (strlen($state) > 4) {
                            return substr($state, 0, 4) . '***';
                        }
                        return '***';
                    })
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Subjek')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diterima')
                    ->dateTime('d M Y, H.i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\Action::make('change_status')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-tag')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ContactStatus::class)
                            ->required()
                            ->selectablePlaceholder(false)
                            ->default(fn ($record) => $record->status->value),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->changeStatus($data['status'] instanceof ContactStatus ? $data['status'] : ContactStatus::from($data['status']));
                    }),
                \Filament\Actions\Action::make('mark_read')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => $record->status === ContactStatus::NEW)
                    ->action(fn ($record) => $record->markAsRead()),
                \Filament\Actions\Action::make('archive')
                    ->label('Arsipkan')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn ($record) => is_null($record->archived_at))
                    ->action(fn ($record) => $record->archive()),
                \Filament\Actions\Action::make('restore')
                    ->label('Pulihkan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn ($record) => !is_null($record->archived_at))
                    ->action(fn ($record) => $record->restoreFromArchive()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
        ];
    }
}