<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use App\Services\SettingsService;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Concerns\InteractsWithForms;

class WebsiteSettings extends Page
{
    use InteractsWithForms;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    protected string $view = 'filament.pages.website-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'village_name' => SettingsService::get('village_name', ''),
            'village_description' => SettingsService::get('village_description', ''),
            'contact_email' => SettingsService::get('contact_email', ''),
            'contact_phone' => SettingsService::get('contact_phone', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identity')
                    ->schema([
                        TextInput::make('village_name')->required(),
                        Textarea::make('village_description'),
                    ]),
                Section::make('Contact')
                    ->schema([
                        TextInput::make('contact_email')->email(),
                        TextInput::make('contact_phone'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            foreach ($data as $key => $value) {
                SettingsService::set($key, $value);
            }

            Notification::make()
                ->success()
                ->title('Settings updated successfully.')
                ->send();
        } catch (Halt $exception) {
            return;
        }
    }
}
