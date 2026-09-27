<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\DocumentCategory;
use App\Models\Media;
use App\Rules\SafeDocumentUpload;
use App\Services\CategoryMutationService;
use App\Services\DocumentFilePolicy;
use App\Services\MediaInputPolicy;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Konten Utama')
                            ->description('Tulis informasi dasar tentang dokumen publik ini.')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Judul Dokumen')
                                    ->required()
                                    ->placeholder('Contoh: Laporan Keuangan Desa Tahun 2026')
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Contoh: Dokumen ini berisi rincian laporan anggaran dan pengeluaran...')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Klasifikasi Dokumen')
                            ->schema([
                                Select::make('document_category_id')
                                    ->label('Kategori Dokumen')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nama Kategori Baru')
                                            ->required()
                                            ->maxLength(150),
                                    ])
                                    ->helperText('Anda dapat membuat kategori baru secara langsung, atau melalui tombol Kelola Kategori.')
                                    ->hintAction(
                                        \Filament\Actions\Action::make('manageCategories')
                                            ->label('Kelola Kategori')
                                            ->icon('heroicon-m-cog-8-tooth')
                                            ->tooltip('Kelola Kategori')
                                            ->modalHeading('Kelola Kategori Dokumen')
                                            ->modalWidth('md')
                                            ->fillForm(fn () => [
                                                'categories' => DocumentCategory::all()->map(fn ($cat) => [
                                                    'id' => $cat->id,
                                                    'name' => $cat->name,
                                                ])->toArray(),
                                                'original_ids' => DocumentCategory::query()->pluck('id')->all(),
                                                'original_versions' => DocumentCategory::query()->get()->mapWithKeys(fn ($cat) => [
                                                    (string) $cat->id => $cat->updated_at?->format('Y-m-d\\TH:i:s.uP'),
                                                ])->all(),
                                            ])
                                            ->form([
                                                Hidden::make('original_ids')->dehydrated(),
                                                Hidden::make('original_versions')->dehydrated(),
                                                \Filament\Forms\Components\Repeater::make('categories')
                                                    ->label('')
                                                    ->schema([
                                                        \Filament\Forms\Components\Hidden::make('id'),
                                                        \Filament\Forms\Components\TextInput::make('name')
                                                            ->required()
                                                            ->hiddenLabel()
                                                            ->placeholder('Nama Kategori Baru')
                                                            ->maxLength(150),
                                                    ])
                                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                                    ->addActionLabel('Tambah Kategori')
                                                    ->reorderable(false)
                                            ])
                                            ->action(function (array $data): void {
                                                app(CategoryMutationService::class)->syncDocuments(
                                                    auth()->user(),
                                                    $data['categories'] ?? [],
                                                    $data['original_ids'] ?? [],
                                                    $data['original_versions'] ?? [],
                                                );
                                            })
                                    ),
                                Hidden::make('status')
                                    ->default('draft'),
                                \Filament\Forms\Components\DateTimePicker::make('published_at')
                                    ->label('Jadwal Publikasi')
                                    ->timezone('Asia/Jakarta')
                                    ->helperText('Kosongkan untuk terbit segera saat dipublikasikan; tanggal mendatang tidak tampil lebih awal.'),
                            ]),
                        Section::make('Media Dokumen')
                            ->description('Unggah PDF, Word, atau Excel, atau pilih berkas dokumen yang sudah tersimpan.')
                            ->schema([
                                FileUpload::make('document_upload')
                                    ->label('Unggah berkas baru')
                                    ->disk('local')
                                    ->directory('originals')
                                    ->visibility('private')
                                    ->maxSize(fn () => app(MediaInputPolicy::class)->maxKilobytes())
                                    ->acceptedFileTypes([
                                        ...array_values(DocumentFilePolicy::MIMES),
                                        'application/zip', 'application/x-ole-storage',
                                        'application/vnd.ms-office', 'application/CDFV2', 'application/octet-stream',
                                    ])
                                    ->rules([new SafeDocumentUpload])
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                        $format = app(DocumentFilePolicy::class)->inspect($file->getRealPath(), $file->getClientOriginalName());
                                        return Str::uuid().'.'.$format['extension'];
                                    })
                                    ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): ?string {
                                        app(DocumentFilePolicy::class)->inspect($file->getRealPath(), $file->getClientOriginalName());
                                        return $component->saveUploadedFile($file);
                                    })
                                    ->storeFileNamesIn('document_upload_name')
                                    ->downloadable(false)
                                    ->openable(false),
                                Select::make('file_media_id')
                                    ->label('Berkas dokumen tersimpan')
                                    ->options(fn () => Media::query()
                                        ->whereIn('extension', array_keys(DocumentFilePolicy::MIMES))
                                        ->whereIn('mime_type', array_values(DocumentFilePolicy::MIMES))
                                        ->orderBy('original_filename')->pluck('original_filename', 'id'))
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
