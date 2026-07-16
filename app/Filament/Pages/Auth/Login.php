<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\Turnstile;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $key = 'login.'.strtolower($data['username']).'.'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('data.username', __('filament-panels::pages/auth/login.messages.throttled', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]));

            return null;
        }

        // Progressive delay via sleep removed in favor of strict rate limiting.

        try {
            $response = parent::authenticate();
            if ($response) {
                RateLimiter::clear($key);
            }

            return $response;
        } catch (ValidationException $e) {
            RateLimiter::hit($key, 900);
            throw ValidationException::withMessages([
                'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                Turnstile::make('captcha'),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
