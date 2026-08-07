<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Schema;
use Filament\Pages\Page;

class MyProfile extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static ?string $title = 'Profil Saya';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user';
    protected static bool $shouldRegisterNavigation = false;

    public static function getLabel(): string
    {
        return 'Profil';
    }

    protected string $view = 'filament.pages.my-profile';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->record(auth()->user())
            ->components([
                Section::make('Informasi Akun')
                    ->schema([
                        TextEntry::make('name')->label('Nama Lengkap'),
                        TextEntry::make('username')->label('Username'),
                        TextEntry::make('email')->label('Alamat Email'),
                        TextEntry::make('created_at')->label('Bergabung Sejak')->dateTime('d F Y'),
                    ])->columns(2)
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Ubah Profil & Kata Sandi')
                ->icon('heroicon-m-pencil-square')
                ->url(EditProfile::getUrl()),
        ];
    }
}
