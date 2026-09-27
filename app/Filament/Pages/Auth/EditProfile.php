<?php

namespace App\Filament\Pages\Auth;

use App\Services\AuditLogService;
use App\Support\AdminPasswordPolicy;
use Closure;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use SensitiveParameter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditProfile extends BaseEditProfile
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Enrollment uses the MFA provider's dedicated state, never this form.
        return Arr::only($data, ['name', 'username', 'email']);
    }

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
            ->required(fn (Get $get): bool => $this->isSensitiveChange($get)
                && filled($this->getUser()->getAttributeValue('app_authentication_secret')))
            ->validationMessages([
                'required' => 'Kode TOTP wajib diisi.',
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
            ->visible(fn (Get $get): bool => $this->isSensitiveChange($get)
                && filled($this->getUser()->getAttributeValue('app_authentication_secret')));
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
            ->required(fn (Get $get): bool => $this->isSensitiveChange($get))
            ->validationMessages([
                'required' => 'Kata sandi saat ini wajib diisi.',
                'current_password' => 'Kata sandi saat ini tidak sesuai.',
            ])
            ->dehydrated(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->rule(AdminPasswordPolicy::rule());
    }

    private function isSensitiveChange(Get $get): bool
    {
        return filled($get('password'))
            || $get('email') !== $this->getUser()->getAttributeValue('email')
            || $get('username') !== $this->getUser()->getAttributeValue('username');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['password'])) {
            $data['force_password_change'] = false;
            $data['password_changed_at'] = now();
        }

        return parent::mutateFormDataBeforeSave($data);
    }

    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record = parent::handleRecordUpdate($record, $data);
            if ($record->wasChanged('password')) {
                AuditLogService::log('password_changed', $record, null, null);
            }

            return $record;
        });
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
