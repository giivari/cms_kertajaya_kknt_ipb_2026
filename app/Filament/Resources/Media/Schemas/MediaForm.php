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
                                    ->helperText('Format yang diterima: JPEG, PNG, WebP, HEIC, Word, atau PDF. Ukuran maksimal 10 MB.')
                                    ->required()
                                    ->acceptedFileTypes([
                                        'image/jpeg', 
                                        'image/png', 
                                        'image/webp', 
                                        'image/heic', 
                                        'application/pdf', 
                                        'application/msword', 
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                    ])
                                    ->maxSize(10240)
                                    ->disk('local')
                                    ->directory('originals')
                                    ->storeFileNamesIn('filename')
                                    ->visibility('private')
                                    ->downloadable(false)
                                    ->openable(false)
                                    ->visibleOn('create'),
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
