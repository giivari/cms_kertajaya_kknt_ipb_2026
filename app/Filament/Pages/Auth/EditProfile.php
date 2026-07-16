<?php

namespace App\Filament\Pages\Auth;

use App\Services\AuditLogService;
use Closure;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use SensitiveParameter;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getUsernameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getTotpConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true);
    }

    protected function getTotpConfirmationFormComponent(): Component
    {
        return TextInput::make('totp')
            ->label('TOTP Confirmation')
            ->requiredWith('password')
            ->dehydrated(false)
            ->rule(function () {
                return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) {
                    if (blank($value)) {
                        return;
                    }
                    $user = Filament::auth()->user();
                    $appAuth = AppAuthentication::make();
                    if (! $appAuth->verifyCode($value, $appAuth->getSecret($user), true)) {
                        $fail('The provided TOTP code is invalid.');
                    }
                };
            })
            ->visible(fn (Get $get): bool => (filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email'))) && filled($this->getUser()->getAttributeValue('app_authentication_secret')));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['password'])) {
            $data['force_password_change'] = false;
            $data['password_changed_at'] = now();
            AuditLogService::log('password_changed', $this->getUser(), null, null);
        }

        return parent::mutateFormDataBeforeSave($data);
    }

    protected function afterSave(): void
    {
        if (isset($this->data['password']) && request()->hasSession()) {
            request()->session()->regenerate();
        }
    }
}
