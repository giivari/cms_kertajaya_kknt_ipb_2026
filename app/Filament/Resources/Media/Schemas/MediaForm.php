<?php

namespace App\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file')
                    ->label('Upload File')
                    ->required()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                    ->maxSize(10240) // 10MB
                    ->disk('private')
                    ->directory('originals')
                    ->storeFileNamesIn('filename') // Will store the actual filename
                    ->visibility('private'),
                TextInput::make('original_filename')
                    ->label('Original Filename')
                    ->required(),
                Textarea::make('alt_text')
                    ->columnSpanFull(),
                Textarea::make('caption')
                    ->columnSpanFull(),
            ]);
    }
}
