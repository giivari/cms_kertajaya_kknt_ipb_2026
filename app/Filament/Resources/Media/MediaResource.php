<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\CreateMedia;
use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Resources\Media\Schemas\MediaForm;
use App\Filament\Resources\Media\Tables\MediaTable;
use App\Models\Media;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MediaResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Lainnya';
    }

    public static function getNavigationLabel(): string
    {
        return 'Perpustakaan Media';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Media';
    }

    public static function getModelLabel(): string
    {
        return 'Media';
    }

    protected static ?string $model = Media::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'original_filename';

    public static function form(Schema $schema): Schema
    {
        return MediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Informasi Media')
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('url')
                            ->label('Pratinjau')
                            ->hidden(fn ($record) => !str_starts_with($record->mime_type, 'image/'))
                            ->columnSpanFull()
                            ->height(250),
                        \Filament\Infolists\Components\TextEntry::make('original_filename')->label('Nama Media'),
                        \Filament\Infolists\Components\TextEntry::make('mime_type')->label('Jenis Berkas'),
                        \Filament\Infolists\Components\TextEntry::make('size')->label('Ukuran')->formatStateUsing(fn ($state) => \Illuminate\Support\Number::fileSize((int) $state, precision: 1)),
                        \Filament\Infolists\Components\TextEntry::make('processing_status')->label('Status Pemrosesan')->badge(),
                        \Filament\Infolists\Components\TextEntry::make('invisible_watermark_status')->label('Status Tanda Air')->badge(),
                        \Filament\Infolists\Components\TextEntry::make('created_at')->label('Diunggah pada')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta'),
                        \Filament\Infolists\Components\TextEntry::make('updated_at')->label('Terakhir Diproses')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta'),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'view' => \App\Filament\Resources\Media\Pages\ViewMedia::route('/{record}'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
