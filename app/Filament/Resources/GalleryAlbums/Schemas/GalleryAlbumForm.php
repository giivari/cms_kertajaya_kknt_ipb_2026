<?php

namespace App\Filament\Resources\GalleryAlbums\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GalleryAlbumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Informasi Album')
                            ->description('Berikan nama dan keterangan singkat agar album mudah dikenali pengunjung.')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Judul Album')
                                    ->placeholder('Contoh: Kegiatan Gotong Royong')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Jelaskan isi atau kegiatan yang didokumentasikan dalam album ini')
                                    ->helperText('Opsional. Gunakan kalimat singkat dan mudah dipahami.')
                                    ->rows(4),
                            ]),
                        Section::make('Foto Galeri')
                            ->description('Pilih media gambar yang sudah selesai diproses dan terverifikasi.')
                            ->schema([
                                Text::make('Belum ada foto di album ini.')
                                    ->visible(fn (Get $get): bool => blank($get('items')))
                                    ->extraAttributes(['class' => 'admin-gallery-empty-items']),
                                Repeater::make('items')
                                    ->hiddenLabel()
                                    ->addActionLabel('Tambah Foto')
                                    ->relationship()
                                    ->schema([
                                        Select::make('media_id')
                                            ->label('Pilih Foto')
                                            ->relationship('media', 'original_filename', fn (Builder $query) => $query->approvedImages())
                                            ->placeholder('Pilih gambar dari Perpustakaan Media')
                                            ->helperText('Hanya gambar yang sudah selesai diproses dan terverifikasi yang tersedia.')
                                            ->required()
                                            ->distinct()
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                        ViewField::make('media_preview')
                                            ->label('Pratinjau Foto')
                                            ->view('filament.forms.gallery-media-thumbnail')
                                            ->viewData(['mediaStatePath' => 'media_id'])
                                            ->dehydrated(false),
                                        TextInput::make('caption')
                                            ->label('Keterangan Foto')
                                            ->placeholder('Keterangan singkat foto')
                                            ->maxLength(500),
                                        TextInput::make('alt_text')
                                            ->label('Teks Alternatif')
                                            ->helperText('Jelaskan isi foto untuk membantu pengunjung yang memakai pembaca layar.')
                                            ->maxLength(255),
                                    ])
                                    ->columns(['md' => 2])
                                    ->itemLabel(fn (array $state): string => filled($state['caption'] ?? null)
                                        ? (string) $state['caption']
                                        : 'Foto Galeri')
                                    ->orderColumn('position')
                                    ->reorderableWithDragAndDrop()
                                    ->collapsible()
                                    ->defaultItems(0),
                            ]),
                    ])
                    ->extraAttributes(['class' => 'admin-gallery-main-column'])
                    ->columnSpan(['lg' => 2]),
                Group::make()
                    ->schema([
                        Section::make('Publikasi')
                            ->description('Tentukan apakah album masih disiapkan atau sudah dapat dilihat pengunjung.')
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draf',
                                        'published' => 'Terbit',
                                        'archived' => 'Diarsipkan',
                                    ])
                                    ->helperText('Waktu publikasi dicatat otomatis saat status menjadi Terbit.')
                                    ->required()
                                    ->default('draft'),
                                Toggle::make('is_featured')
                                    ->label('Jadikan Galeri Unggulan')
                                    ->helperText('Galeri unggulan dapat ditampilkan lebih menonjol pada website.')
                                    ->default(false),
                            ]),
                        Section::make('Gambar Sampul')
                            ->description('Sampul bersifat opsional dan dipilih dari media yang sudah terverifikasi.')
                            ->schema([
                                Select::make('cover_media_id')
                                    ->label('Pilih Gambar')
                                    ->relationship('coverMedia', 'original_filename', fn (Builder $query) => $query->approvedImages())
                                    ->placeholder('Belum ada gambar dipilih')
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                                ViewField::make('cover_preview')
                                    ->label('Pratinjau Sampul')
                                    ->view('filament.forms.gallery-media-thumbnail')
                                    ->viewData(['mediaStatePath' => 'cover_media_id'])
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->extraAttributes(['class' => 'admin-gallery-side-column'])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3)
            ->extraAttributes(['class' => 'admin-content-form admin-gallery-form']);
    }
}
