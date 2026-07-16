<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;

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
            'logo_placeholder' => SettingsService::get('logo_placeholder', 'To be implemented in Sprint 2'),
            'contact_email' => SettingsService::get('contact_email', ''),
            'contact_phone' => SettingsService::get('contact_phone', ''),
            'address_street' => SettingsService::get('address_street', ''),
            'address_village' => SettingsService::get('address_village', ''),
            'address_subdistrict' => SettingsService::get('address_subdistrict', ''),
            'address_district' => SettingsService::get('address_district', ''),
            'address_province' => SettingsService::get('address_province', ''),
            'address_postal_code' => SettingsService::get('address_postal_code', ''),
            'social_facebook' => SettingsService::get('social_facebook', ''),
            'social_instagram' => SettingsService::get('social_instagram', ''),
            'social_twitter' => SettingsService::get('social_twitter', ''),
            'social_youtube' => SettingsService::get('social_youtube', ''),
            'meta_title' => SettingsService::get('meta_title', ''),
            'meta_description' => SettingsService::get('meta_description', ''),
            'footer_copyright_text' => SettingsService::get('footer_copyright_text', ''),
            'watermark_image_placeholder' => SettingsService::get('watermark_image_placeholder', 'To be implemented in Sprint 2'),
            'watermark_text' => SettingsService::get('watermark_text', 'Village CMS'),
            'watermark_opacity' => SettingsService::get('watermark_opacity', 50),
            'enable_visible_watermark' => SettingsService::get('enable_visible_watermark', false),
            'watermark_position' => SettingsService::get('watermark_position', 'bottom-right'),
            'watermark_scale' => SettingsService::get('watermark_scale', 20),
            'max_upload_size' => SettingsService::get('max_upload_size', 10),
            'max_image_width' => SettingsService::get('max_image_width', 3840),
            'max_image_height' => SettingsService::get('max_image_height', 2160),
            'processing_timeout' => SettingsService::get('processing_timeout', 120),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Village Identity')
                    ->schema([
                        TextInput::make('village_name')->required(),
                        Textarea::make('village_description'),
                        TextInput::make('logo_placeholder')->label('Logo Placeholder')->disabled(),
                    ])->columns(2),
                Section::make('Contact & Address')
                    ->schema([
                        TextInput::make('contact_email')->email(),
                        TextInput::make('contact_phone'),
                        Textarea::make('address_street'),
                        TextInput::make('address_village'),
                        TextInput::make('address_subdistrict'),
                        TextInput::make('address_district'),
                        TextInput::make('address_province'),
                        TextInput::make('address_postal_code'),
                    ])->columns(2),
                Section::make('Social Media')
                    ->schema([
                        TextInput::make('social_facebook')->url(),
                        TextInput::make('social_instagram')->url(),
                        TextInput::make('social_twitter')->url(),
                        TextInput::make('social_youtube')->url(),
                    ])->columns(2),
                Section::make('SEO & Footer')
                    ->schema([
                        TextInput::make('meta_title'),
                        Textarea::make('meta_description'),
                        TextInput::make('footer_copyright_text'),
                    ])->columns(1),
                Section::make('Branding & Watermark')
                    ->schema([
                        Toggle::make('enable_visible_watermark')->label('Enable Visible Watermark'),
                        TextInput::make('watermark_text'),
                        TextInput::make('watermark_opacity')->numeric()->minValue(0)->maxValue(100),
                        Select::make('watermark_position')->options([
                            'top-left' => 'Top Left',
                            'top-right' => 'Top Right',
                            'bottom-left' => 'Bottom Left',
                            'bottom-right' => 'Bottom Right',
                            'center' => 'Center',
                        ]),
                        TextInput::make('watermark_scale')->numeric()->minValue(1)->maxValue(100)->label('Scale (%)'),
                    ])->columns(2),
                Section::make('Media & Processing Limits')
                    ->schema([
                        TextInput::make('max_upload_size')->numeric()->minValue(1)->maxValue(50)->label('Max Upload Size (MB)'),
                        TextInput::make('max_image_width')->numeric()->minValue(100)->maxValue(8000)->label('Max Image Width (px)'),
                        TextInput::make('max_image_height')->numeric()->minValue(100)->maxValue(8000)->label('Max Image Height (px)'),
                        TextInput::make('processing_timeout')->numeric()->minValue(10)->maxValue(600)->label('Processing Timeout (sec)'),
                    ])->columns(2),
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
