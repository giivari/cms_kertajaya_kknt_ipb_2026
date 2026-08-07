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
        $isForced = Filament::auth()->check() ? Filament::auth()->user()->force_password_change : false;

        $components = [];

        if (! $isForced) {
            $components[] = $this->getNameFormComponent();
            $components[] = $this->getUsernameFormComponent();
            $components[] = $this->getEmailFormComponent();
        }

        $components[] = $this->getPasswordFormComponent();
        $components[] = $this->getPasswordConfirmationFormComponent();
        $components[] = $this->getTotpConfirmationFormComponent();
        $components[] = $this->getCurrentPasswordFormComponent();

        return $schema->components($components);
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
            ->label('Kode TOTP')
            ->requiredWith('password')
            ->validationMessages([
                'required_with' => 'Kode TOTP wajib diisi.',
            ])
            ->dehydrated(false)
            ->rule(function () {
                return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) {
                    if (blank($value)) {
                        return;
                    }
                    $user = Filament::auth()->user();
                    $appAuth = AppAuthentication::make();
                    if (! $appAuth->verifyCode($value, $appAuth->getSecret($user), true)) {
                        $fail('Kode TOTP tidak valid.');
                    }
                };
            })
            ->visible(fn (Get $get): bool => (filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email'))) && filled($this->getUser()->getAttributeValue('app_authentication_secret')));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Konfirmasi kata sandi baru')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->requiredWith('password')
            ->same('password')
            ->validationMessages([
                'required_with' => 'Konfirmasi kata sandi baru wajib diisi.',
                'same' => 'Konfirmasi kata sandi baru tidak cocok.',
            ])
            ->visible(fn (Get $get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('currentPassword')
            ->label('Kata sandi saat ini')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->currentPassword(guard: Filament::getAuthGuard())
            ->requiredWith('password')
            ->validationMessages([
                'required_with' => 'Kata sandi saat ini wajib diisi.',
                'current_password' => 'Kata sandi saat ini tidak sesuai.',
            ])
            ->dehydrated(false);
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
        if (isset($this->data['password']) && app()->has('session.store')) {
            session()->regenerate();

            // Full redirect to refresh CSRF token in the DOM and prevent 419 on logout
            $this->redirect(filament()->getUrl());
        }
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()->extraAttributes(['formnovalidate' => 'formnovalidate']);
    }
}
