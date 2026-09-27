<?php

namespace App\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Unggah Media')
                            ->icon('heroicon-o-cloud-arrow-up')
                            ->description('Berkas asli disimpan secara privat dan diproses sebelum dapat digunakan pada website.')
                            ->schema([
                                FileUpload::make('file')
                                    ->label('Pilih Berkas')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                        '3:4',
                                        '9:16',
                                    ])
                                    ->helperText('JPEG, PNG, WebP, HEIC, Word, atau PDF; maksimal 10 MB atau batas pengaturan yang lebih kecil. Word tetap privat karena belum didukung pemrosesan publik.')
                                    ->required()
                                    ->acceptedFileTypes(array_keys(\App\Services\MediaInputPolicy::EXTENSIONS))
                                    ->maxSize(fn () => app(\App\Services\MediaInputPolicy::class)->maxKilobytes())
                                    ->rules([new \App\Rules\SafeMediaUpload()])
                                    ->saveUploadedFileUsing(function (FileUpload $component, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file): ?string {
                                        app(\App\Services\MediaInputPolicy::class)->inspect($file->getRealPath(), $file->getClientOriginalName());
                                        return $component->saveUploadedFile($file);
                                    })
                                    ->disk('local')
                                    ->directory('originals')
                                    ->visibility('private')
                                    ->getUploadedFileUsing(function (string $file, ?\App\Models\Media $record): ?array {
                                        if (! $record || $record->trashed() || $record->disk !== 'local' ||
                                            $file !== 'originals/'.$record->filename ||
                                            ! \Illuminate\Support\Facades\Gate::allows('update', $record)) {
                                            return null;
                                        }

                                        return [
                                            'name' => $record->original_filename,
                                            'size' => $record->size,
                                            'type' => $record->mime_type,
                                            'url' => route('admin.media.original', $record),
                                        ];
                                    })
                                    ->downloadable(false)
                                    ->openable(false),
                                \Filament\Forms\Components\ViewField::make('preview')
                                    ->label('Pratinjau Media')
                                    ->view('filament.forms.media-preview')
                                    ->visibleOn('edit'),
                            ]),
                    ])
                    ->extraAttributes(['class' => 'admin-media-upload-main'])
                    ->columnSpan(['lg' => 3]),
                Group::make()
                    ->schema([
                        Section::make('Informasi Media')
                            ->description('Informasi ini membantu admin mengenali media tanpa menampilkan nama berkas sistem.')
                            ->schema([
                                TextInput::make('original_filename')
                                    ->label('Nama Media')
                                    ->helperText('Gunakan nama yang mudah dikenali saat memilih media di fitur lain.')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('alt_text')
                                    ->label('Teks Alternatif')
                                    ->helperText('Jelaskan isi gambar untuk membantu pengunjung yang memakai pembaca layar.')
                                    ->rows(3)
                                    ->maxLength(255),
                                Textarea::make('caption')
                                    ->label('Keterangan')
                                    ->helperText('Opsional. Keterangan dapat digunakan saat media ditampilkan pada konten.')
                                    ->rows(4),
                            ]),
                    ])
                    ->extraAttributes(['class' => 'admin-media-upload-side'])
                    ->columnSpan(['lg' => 2]),
            ])
            ->columns(5)
            ->extraAttributes(['class' => 'admin-content-form admin-media-upload']);
    }
}
