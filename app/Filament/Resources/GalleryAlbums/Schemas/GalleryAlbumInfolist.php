<?php

namespace App\Filament\Resources\GalleryAlbums\Schemas;

use App\Filament\Support\MediaThumbnail;
use App\Models\GalleryAlbum;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GalleryAlbumInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Galeri')
                ->schema([
                    ImageEntry::make('cover_thumbnail')
                        ->label('Gambar Sampul')
                        ->state(fn (GalleryAlbum $record): ?string => MediaThumbnail::path($record->coverMedia))
                        ->disk(fn (GalleryAlbum $record): string => MediaThumbnail::disk($record->coverMedia))
                        ->defaultImageUrl(MediaThumbnail::placeholderUrl())
                        ->extraImgAttributes(fn (GalleryAlbum $record): array => [
                            'class' => 'admin-gallery-detail-cover',
                            'alt' => 'Sampul album '.$record->title,
                        ])
                        ->square()
                        ->imageHeight(160),
                    TextEntry::make('title')->label('Judul Album'),
                    TextEntry::make('items_count')->label('Jumlah Foto')->state(fn ($record) => $record->items()->count()),
                    TextEntry::make('description')->label('Deskripsi')->placeholder('Belum ada deskripsi')->columnSpanFull(),
                    TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Terbit', 'archived' => 'Diarsipkan', default => 'Draf',
                    }),
                    TextEntry::make('published_at')->label('Diterbitkan pada')->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')->placeholder('Belum diterbitkan'),
                ])
                ->columns(['md' => 2])
                ->extraAttributes(['class' => 'admin-gallery-detail']),
        ]);
    }
}
