<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Exports\PageExporter;
use App\Filament\Support\AdminTable;
use App\Enums\DerivativeType;
use App\Enums\InvisibleWatermarkStatus;
use App\Enums\MediaProcessingStatus;
use App\Enums\PageStatus;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Pages\ViewPage;
use App\Filament\Resources\Pages\Schemas\PageInfolist;
use App\Models\Page;
use App\Services\PageTemplateService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return 'Kelola Konten';
    }

    public static function getNavigationLabel(): string
    {
        return 'Halaman';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Halaman';
    }

    public static function getModelLabel(): string
    {
        return 'Halaman';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    protected static ?string $model = Page::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function validMediaQuery(EloquentBuilder $query, ?string $mimeTypePrefix = null): EloquentBuilder
    {
        $query->where('processing_status', MediaProcessingStatus::COMPLETED->value)
            ->where('invisible_watermark_status', InvisibleWatermarkStatus::VERIFIED->value)
            ->whereHas('derivatives', function ($q) {
                $q->whereIn('derivative_type', [DerivativeType::PUBLIC->value, DerivativeType::PUBLIC_VISIBLE_WATERMARK->value]);
            });

        if ($mimeTypePrefix) {
            $query->where('mime_type', 'like', $mimeTypePrefix.'%');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Informasi Halaman')
                            ->description('Isi dasar halaman yang akan dikenali dan dibaca oleh pengunjung.')
                            ->schema([
                                Text::make('Halaman berisi informasi yang dapat ditampilkan kepada pengunjung. Setelah dibuat, halaman dapat ditambahkan ke Menu.'),
                                Select::make('template')
                                    ->label('Templat')
                                    ->options(app(PageTemplateService::class)->getAvailableTemplates())
                                    ->default('blank')
                                    ->visible(fn ($livewire) => $livewire instanceof CreatePage)
                                    ->helperText('Pilih templat untuk mengisi susunan awal halaman.'),
                                TextInput::make('title')
                                    ->label('Judul')
                                    ->placeholder('Masukkan judul halaman')
                                    ->maxLength(255)
                                    ->required(),
                                Forms\Components\Textarea::make('excerpt')
                                    ->label('Ringkasan')
                                    ->placeholder('Ringkasan singkat isi halaman')
                                    ->helperText('Opsional. Ringkasan dapat digunakan pada daftar tautan dan hasil pencarian.')
                                    ->rows(3),
                            ]),

                        Section::make('Penyusun Halaman')
                            ->description('Susun bagian dan blok konten sesuai urutan tampil pada website.')
                            ->schema([
                                Repeater::make('builder_sections')
                                    ->label('Bagian Halaman')
                                    ->helperText('Gunakan tombol urutan untuk memindahkan bagian tanpa mengubah isinya.')
                                    ->addActionLabel('Tambah Bagian')
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Bagian Baru')
                                    ->schema([
                                        Hidden::make('id'),
                                        TextInput::make('name')->label('Nama Bagian')->required(),
                                        Select::make('layout_type')
                                            ->label('Tata Letak')
                                            ->options([
                                                'single_column' => 'Satu Kolom',
                                                'two_columns' => 'Dua Kolom',
                                                'three_columns' => 'Tiga Kolom',
                                                'hero' => 'Sorotan Utama',
                                                'full_width' => 'Lebar Penuh',
                                            ])
                                            ->default('single_column')
                                            ->required(),
                                        Forms\Components\Toggle::make('is_visible')->default(true)->label('Tampilkan'),
                                        Builder::make('components')
                                            ->label('Blok Konten')
                                            ->addActionLabel('Tambah Blok')
                                            ->blocks([
                                                Block::make('heading')
                                                    ->label('Judul')
                                                    ->schema([
                                                        TextInput::make('text')->label('Teks')->required(),
                                                        Select::make('level')->label('Tingkat Judul')->options(['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4'])->default('h2')->required(),
                                                        Select::make('alignment')->label('Perataan')->options(['left' => 'Kiri', 'center' => 'Tengah', 'right' => 'Kanan'])->default('left'),
                                                    ]),
                                                Block::make('rich_text')
                                                    ->label('Teks Lengkap')
                                                    ->schema([
                                                        Forms\Components\RichEditor::make('content')->label('Isi')->required(),
                                                    ]),
                                                Block::make('image')
                                                    ->label('Gambar')
                                                    ->schema([
                                                        Select::make('media_id')
                                                            ->label('Pilih Gambar')
                                                            ->relationship('featuredMedia', 'original_filename', fn (EloquentBuilder $query) => static::validMediaQuery($query, 'image/'))
                                                            ->searchable()
                                                            ->required(),
                                                        TextInput::make('caption')->label('Keterangan'),
                                                        TextInput::make('alt_text')->label('Teks Alternatif'),
                                                    ]),
                                                Block::make('gallery')
                                                    ->label('Galeri')
                                                    ->schema([
                                                        Select::make('images')
                                                            ->label('Pilih Gambar')
                                                            ->multiple()
                                                            ->relationship('featuredMedia', 'original_filename', fn (EloquentBuilder $query) => static::validMediaQuery($query, 'image/'))
                                                            ->searchable(),
                                                    ]),
                                                Block::make('statistics')
                                                    ->label('Statistik')
                                                    ->schema([
                                                        Repeater::make('items')
                                                            ->label('Data Statistik')
                                                            ->schema([
                                                                TextInput::make('label')->label('Nama')->required(),
                                                                TextInput::make('value')->label('Nilai')->required(),
                                                                TextInput::make('icon')->label('Ikon'),
                                                            ]),
                                                    ]),
                                                Block::make('video')
                                                    ->label('Video')
                                                    ->schema([
                                                        TextInput::make('video_url')->label('Alamat Video')->url()->required(),
                                                        TextInput::make('caption')->label('Keterangan'),
                                                    ]),
                                                Block::make('map')
                                                    ->label('Peta')
                                                    ->schema([
                                                        TextInput::make('latitude')->label('Garis Lintang')->numeric()->required(),
                                                        TextInput::make('longitude')->label('Garis Bujur')->numeric()->required(),
                                                        TextInput::make('zoom')->label('Tingkat Pembesaran')->numeric()->default(15),
                                                    ]),
                                                Block::make('documents')
                                                    ->label('Dokumen')
                                                    ->schema([
                                                        Select::make('documents')
                                                            ->label('Pilih Dokumen')
                                                            ->multiple()
                                                            ->relationship('featuredMedia', 'original_filename', fn (EloquentBuilder $query) => static::validMediaQuery($query)->where('mime_type', 'application/pdf'))
                                                            ->searchable(),
                                                    ]),
                                                Block::make('cta_button')
                                                    ->label('Tombol Tautan')
                                                    ->schema([
                                                        TextInput::make('text')->label('Teks')->required(),
                                                        TextInput::make('url')->label('Alamat Tautan')->required(),
                                                        Select::make('style')->label('Tampilan')->options(['primary' => 'Utama', 'secondary' => 'Sekunder', 'outline' => 'Garis Tepi'])->default('primary'),
                                                    ]),
                                                Block::make('card_grid')
                                                    ->label('Kumpulan Kartu')
                                                    ->schema([
                                                        Repeater::make('cards')
                                                            ->label('Daftar Kartu')
                                                            ->schema([
                                                                TextInput::make('title')->label('Judul')->required(),
                                                                Forms\Components\Textarea::make('description')->label('Deskripsi'),
                                                                TextInput::make('link_url')->label('Alamat Tautan'),
                                                            ]),
                                                    ]),
                                                Block::make('contact_block')
                                                    ->label('Informasi Kontak')
                                                    ->schema([
                                                        TextInput::make('email')->label('Email')->email(),
                                                        TextInput::make('phone')->label('Nomor Telepon'),
                                                        Forms\Components\Textarea::make('address')->label('Alamat'),
                                                    ]),
                                            ])
                                            ->collapsed(),
                                    ])
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                        Section::make('Pengaturan Lanjutan')
                            ->description('Pengaturan opsional untuk tampilan halaman pada mesin pencari.')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('Judul untuk Mesin Pencari')
                                    ->helperText('Jika kosong, judul halaman akan digunakan.')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('seo_description')
                                    ->label('Deskripsi untuk Mesin Pencari')
                                    ->helperText('Jika kosong, ringkasan halaman akan digunakan.')
                                    ->rows(3)
                                    ->maxLength(320),
                            ])
                            ->columns(['md' => 2])
                            ->collapsible()
                            ->collapsed(),
                    ])
                    ->extraAttributes(['class' => 'admin-form-main-column'])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Publikasi')
                            ->description('Pilih status halaman. Waktu publikasi dicatat otomatis ketika status menjadi Terbit.')
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        PageStatus::DRAFT->value => 'Draf',
                                        PageStatus::PUBLISHED->value => 'Terbit',
                                        PageStatus::ARCHIVED->value => 'Diarsipkan',
                                    ])
                                    ->default(PageStatus::DRAFT->value)
                                    ->required(),
                            ]),
                        Section::make('Gambar Utama')
                            ->description('Gambar opsional dari Perpustakaan Media yang sudah terverifikasi.')
                            ->schema([
                                Select::make('featured_media_id')
                                    ->label('Pilih Gambar')
                                    ->relationship('featuredMedia', 'original_filename', fn (EloquentBuilder $query) => static::validMediaQuery($query, 'image/'))
                                    ->searchable()
                                    ->placeholder('Belum ada gambar dipilih'),
                            ]),
                    ])
                    ->extraAttributes(['class' => 'admin-form-side-column'])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3)
            ->extraAttributes(['class' => 'admin-content-form']);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table, 'admin-content-table')
            ->recordUrl(fn ($record): string => static::getUrl('view', ['record' => $record]))
            ->searchPlaceholder('Cari judul halaman...')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Halaman')
                    ->description(fn (Page $record): ?string => filled($record->excerpt) ? Str::limit($record->excerpt, 72) : null)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state): string => match ($state instanceof PageStatus ? $state->value : $state) {
                        'published' => 'Terbit', 'archived' => 'Diarsipkan', default => 'Draf',
                    })
                    ->color(fn ($state): string => match ($state instanceof PageStatus ? $state->value : $state) {
                        'published' => 'success', 'archived' => 'warning', default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Diterbitkan pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->placeholder('Belum diterbitkan')
                    ->visibleFrom('md')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->visibleFrom('md')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime('d/m/Y H.i', timezone: 'Asia/Jakarta')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        PageStatus::DRAFT->value => 'Draf',
                        PageStatus::PUBLISHED->value => 'Terbit',
                        PageStatus::ARCHIVED->value => 'Diarsipkan',
                    ]),
                Tables\Filters\TrashedFilter::make()->label('Status Penghapusan'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Lihat')->icon('heroicon-o-eye'),
                    EditAction::make()->label('Ubah')->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Hapus Halaman')
                        ->modalDescription('Halaman akan dihapus dan tautan publiknya tidak lagi dapat dibuka.')
                        ->modalSubmitActionLabel('Hapus'),
                    ForceDeleteAction::make()->label('Hapus Permanen')->icon('heroicon-o-trash'),
                    RestoreAction::make()->label('Pulihkan')->icon('heroicon-o-arrow-path'),
                ])
                    ->label('Aksi Halaman')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Aksi Halaman'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                AdminTable::exportAction(PageExporter::class, self::class),
            ])
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('Belum ada halaman')
            ->emptyStateDescription('Buat halaman pertama untuk menampilkan informasi tetap kepada pengunjung.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return PageInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'view' => ViewPage::route('/{record}'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
