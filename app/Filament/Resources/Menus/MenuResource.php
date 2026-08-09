<?php

namespace App\Filament\Resources\Menus;

use App\Enums\LinkType;
use App\Enums\PageStatus;
use App\Filament\Exports\MenuExporter;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Models\Menu;
use App\Filament\Support\AdminTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MenuResource extends Resource
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    public static function getNavigationGroup(): ?string
    {
        return 'Kelola Website';
    }

    public static function getNavigationLabel(): string
    {
        return 'Navigasi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Menu';
    }

    public static function getModelLabel(): string
    {
        return 'Menu';
    }

    protected static ?string $model = Menu::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-bars-3';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Tentang Menu')
                    ->schema([
                        Text::make(function (?Menu $record) {
                            if (! $record) {
                                return 'Menu mengatur tombol navigasi yang tampil di website. Menu tidak membuat isi baru; buat isi melalui Halaman, lalu tambahkan halaman tersebut sebagai tautan di sini.';
                            }
                            return "Anda sedang mengelola tautan navigasi untuk {$record->name}. Menu mengatur tombol navigasi yang tampil di website. Menu tidak membuat isi baru; buat isi melalui Halaman, lalu tambahkan halaman tersebut sebagai tautan di sini.";
                        })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Tautan yang Ditampilkan')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->helperText('Belum ada tautan. Tambahkan tautan agar menu tampil di website.')
                            ->addActionLabel('Tambah Tautan')
                            ->relationship('items')
                            ->schema([
                                TextInput::make('label')->label('Nama yang Tampil')->required(),
                                Select::make('link_type')
                                    ->label('Tujuan Tautan')
                                    ->options([
                                        LinkType::PAGE->value => 'Halaman Website',
                                        LinkType::HOME->value => 'Beranda',
                                        LinkType::NEWS_INDEX->value => 'Daftar Berita',
                                        LinkType::GALLERY_INDEX->value => 'Daftar Galeri',
                                        LinkType::DOCUMENT_INDEX->value => 'Daftar Dokumen',
                                        LinkType::MAP->value => 'Peta',
                                        LinkType::CONTACT->value => 'Kontak',
                                        LinkType::CUSTOM->value => 'Tautan Luar',
                                    ])
                                    ->default(LinkType::CUSTOM->value)
                                    ->live()
                                    ->required(),
                                Select::make('page_id')
                                    ->label('Halaman yang Dituju')
                                    ->relationship('page', 'title', fn ($query) => $query->where('status', PageStatus::PUBLISHED->value))
                                    ->searchable()
                                    ->visible(fn (Get $get) => $get('link_type') === LinkType::PAGE->value)
                                    ->required(fn (Get $get) => $get('link_type') === LinkType::PAGE->value),
                                TextInput::make('custom_url')
                                    ->label('Alamat Tautan')
                                    ->url()
                                    ->regex('/^https?:\/\//i')
                                    ->visible(fn (Get $get) => $get('link_type') === LinkType::CUSTOM->value)
                                    ->required(fn (Get $get) => $get('link_type') === LinkType::CUSTOM->value),
                                Toggle::make('target')
                                    ->label('Buka di Tab Baru')
                                    ->formatStateUsing(fn ($state): bool => $state === '_blank' || $state === true)
                                    ->dehydrateStateUsing(fn ($state): string => ($state === true || $state === '1' || $state === '_blank') ? '_blank' : '_self')
                                    ->default(false),
                                Toggle::make('is_visible')->label('Tampilkan')->default(true),

                                Repeater::make('children')
                                    ->label('Tautan Turunan (Opsional)')
                                    ->addActionLabel('Tambah Tautan Turunan')
                                    ->relationship('children')
                                    ->schema([
                                        TextInput::make('label')->label('Nama yang Tampil')->required(),
                                        Select::make('link_type')
                                            ->label('Tujuan Tautan')
                                            ->options([
                                                LinkType::PAGE->value => 'Halaman Website',
                                                LinkType::HOME->value => 'Beranda',
                                                LinkType::NEWS_INDEX->value => 'Daftar Berita',
                                                LinkType::GALLERY_INDEX->value => 'Daftar Galeri',
                                                LinkType::DOCUMENT_INDEX->value => 'Daftar Dokumen',
                                                LinkType::MAP->value => 'Peta',
                                                LinkType::CONTACT->value => 'Kontak',
                                                LinkType::CUSTOM->value => 'Tautan Luar',
                                            ])
                                            ->default(LinkType::CUSTOM->value)
                                            ->live()
                                            ->required(),
                                        Select::make('page_id')
                                            ->label('Halaman yang Dituju')
                                            ->relationship('page', 'title', fn ($query) => $query->where('status', PageStatus::PUBLISHED->value))
                                            ->searchable()
                                            ->visible(fn (Get $get) => $get('link_type') === LinkType::PAGE->value)
                                            ->required(fn (Get $get) => $get('link_type') === LinkType::PAGE->value),
                                        TextInput::make('custom_url')
                                            ->label('Alamat Tautan')
                                            ->url()
                                            ->regex('/^https?:\/\//i')
                                            ->visible(fn (Get $get) => $get('link_type') === LinkType::CUSTOM->value)
                                            ->required(fn (Get $get) => $get('link_type') === LinkType::CUSTOM->value),
                                        Toggle::make('target')
                                            ->label('Buka di Tab Baru')
                                            ->formatStateUsing(fn ($state): bool => $state === '_blank' || $state === true)
                                            ->dehydrateStateUsing(fn ($state): string => ($state === true || $state === '1' || $state === '_blank') ? '_blank' : '_self')
                                            ->default(false),
                                        Toggle::make('is_visible')->label('Tampilkan')->default(true),
                                    ])
                                    ->orderColumn('position')
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                            ])
                            ->orderColumn('position')
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => filled($state['label'] ?? null) ? $state['label'] : 'Nama yang Tampil')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Pratinjau Navigasi')
                    ->description('Gunakan tombol Pratinjau di bagian bawah form untuk melihat susunan navigasi desktop, mobile, atau kaki halaman sebelum disimpan.')
                    ->visible(fn (): bool => config('preview.ui_enabled', false))
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => EditMenu::route('/'),
        ];
    }
}
