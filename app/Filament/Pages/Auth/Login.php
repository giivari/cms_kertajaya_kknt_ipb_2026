<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\Turnstile;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $response = parent::authenticate();
            
            // Logout other devices to enforce single session
            \Illuminate\Support\Facades\Auth::logoutOtherDevices($this->data['password']);
            
            return $response;
        } catch (ValidationException $e) {
            $this->dispatch('turnstile-reset');
            
            // Filament membuang error ke 'data.email' secara bawaan.
            // Kita petakan ulang ke 'data.username' dan paksa menggunakan Bahasa Indonesia.
            if (isset($e->validator->errors()->messages()['data.email'])) {
                throw ValidationException::withMessages([
                    'data.username' => 'Kombinasi nama pengguna dan kata sandi salah.',
                ]);
            }
            
            throw $e;
        }
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable
    {
        return filled($this->userUndertakingMultiFactorAuthentication)
            ? 'Verifikasi Dua Faktor'
            : 'Masuk ke Panel Admin';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication)
            ? 'Konfirmasi identitas Anda untuk melanjutkan.'
            : '';
    }

    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()->label('Masuk');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                Turnstile::make('captcha'),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Nama pengguna')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata sandi')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->autocomplete('current-password');
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
