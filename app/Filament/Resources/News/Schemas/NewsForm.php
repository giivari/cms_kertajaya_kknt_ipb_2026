<?php

namespace App\Filament\Resources\News\Schemas;

use App\Filament\Resources\NewsCategories\NewsCategoryResource;
use App\Filament\Resources\NewsCategories\Schemas\NewsCategoryForm;
use App\Models\NewsCategory;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Konten Utama')
                            ->description('Tulis informasi yang akan dibaca pengunjung pada halaman berita.')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Judul Berita')
                                    ->placeholder('Masukkan judul berita yang jelas dan singkat')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('excerpt')
                                    ->label('Ringkasan')
                                    ->placeholder('Ringkasan singkat yang tampil pada daftar berita')
                                    ->helperText('Opsional, maksimal 500 karakter.')
                                    ->rows(3)
                                    ->maxLength(500),
                                RichEditor::make('content')
                                    ->label('Isi Berita')
                                    ->placeholder('Mulai menulis isi berita...')
                                    ->required(),
                            ]),
                        Section::make('Pengaturan Lanjutan')
                            ->description('Pengaturan opsional untuk tampilan berita pada mesin pencari.')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('Judul untuk Mesin Pencari')
                                    ->helperText('Jika kosong, judul berita akan digunakan.')
                                    ->maxLength(255),
                                Textarea::make('seo_description')
                                    ->label('Deskripsi untuk Mesin Pencari')
                                    ->helperText('Jika kosong, ringkasan berita akan digunakan.')
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
                            ->description('Tentukan apakah berita masih disiapkan atau sudah dapat dibaca pengunjung.')
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
                            ]),
                        Section::make('Klasifikasi')
                            ->description('Kelompokkan berita agar lebih mudah ditemukan.')
                            ->schema([
                                Select::make('news_category_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Pilih kategori berita')
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nama Kategori Baru')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->helperText('Anda dapat membuat kategori baru secara langsung, atau melalui tombol Kelola Kategori.')
                                    ->suffixAction(
                                        Action::make('manageCategories')
                                            ->label('Kelola Kategori')
                                            ->icon('heroicon-m-cog-8-tooth')
                                            ->tooltip('Kelola Kategori')
                                            ->modalHeading('Kelola Kategori Berita')
                                            ->modalWidth('md')
                                            ->fillForm(fn () => [
                                                'categories' => NewsCategory::all()->map(fn ($cat) => [
                                                    'id' => $cat->id,
                                                    'name' => $cat->name,
                                                ])->toArray(),
                                            ])
                                            ->form([
                                                Repeater::make('categories')
                                                    ->label('')
                                                    ->schema([
                                                        Hidden::make('id'),
                                                        TextInput::make('name')
                                                            ->required()
                                                            ->hiddenLabel()
                                                            ->placeholder('Nama Kategori Baru'),
                                                    ])
                                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                                    ->addActionLabel('Tambah Kategori')
                                                    ->reorderable(false)
                                            ])
                                            ->action(function (array $data) {
                                                $submittedIds = collect($data['categories'])->pluck('id')->filter()->toArray();
                                                NewsCategory::whereNotIn('id', $submittedIds)->delete();
                                                
                                                foreach ($data['categories'] as $catData) {
                                                    if (!empty($catData['name'])) {
                                                        NewsCategory::updateOrCreate(
                                                            ['id' => $catData['id'] ?? null],
                                                            ['name' => $catData['name']]
                                                        );
                                                    }
                                                }
                                            })
                                    ),
                                Toggle::make('is_featured')
                                    ->label('Jadikan Berita Unggulan')
                                    ->helperText('Berita unggulan dapat ditampilkan lebih menonjol pada website.')
                                    ->default(false),
                            ]),
                        Section::make('Gambar Utama')
                            ->description('Gambar bersifat opsional dan dipilih dari Perpustakaan Media yang sudah terverifikasi.')
                            ->schema([
                                Select::make('featured_media_id')
                                    ->label('Pilih Gambar')
                                    ->relationship('featuredMedia', 'original_filename', fn (Builder $query) => $query->approvedImages())
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Belum ada gambar dipilih'),
                            ]),
                    ])
                    ->extraAttributes(['class' => 'admin-form-side-column'])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3)
            ->extraAttributes(['class' => 'admin-content-form']);
    }
}
