<?php

namespace App\Filament\Pages;

use App\Models\Media;
use App\Filament\Support\PreviewAction;
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

    protected static ?string $title = 'Pengaturan Website';

    public static function getNavigationLabel(): string
    {
        return 'Tampilan & Identitas';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Kelola Website';
    }

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.website-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'village_name' => SettingsService::get('village_name', 'Desa Kertajaya'),
            'village_description' => SettingsService::get('village_description', ''),
            'village_logo' => SettingsService::get('village_logo', null),
            'favicon' => SettingsService::get('favicon', null),
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
            'watermark_image' => SettingsService::get('watermark_image', null),
            'watermark_text' => SettingsService::get('watermark_text', 'CMS Desa Kertajaya'),
            'watermark_opacity' => SettingsService::get('watermark_opacity', 50),
            'enable_visible_watermark' => SettingsService::get('enable_visible_watermark', false),
            'watermark_position' => SettingsService::get('watermark_position', 'bottom-right'),
            'watermark_scale' => SettingsService::get('watermark_scale', 20),
            'max_upload_size' => SettingsService::get('max_upload_size', 10),
            'max_image_width' => SettingsService::get('max_image_width', 3840),
            'max_image_height' => SettingsService::get('max_image_height', 2160),
            'processing_timeout' => SettingsService::get('processing_timeout', 120),
            'notification_email' => SettingsService::get('notification_email', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $mediaOptions = fn () => Media::where('invisible_watermark_status', 'verified')
            ->pluck('original_filename', 'id');

        return $schema
            ->schema([
                Section::make('Identitas Desa')
                    ->schema([
                        TextInput::make('village_name')->label('Nama Desa')->required(),
                        Textarea::make('village_description')->label('Deskripsi Singkat'),
                    ])->columns(2),
                Section::make('Media dan Logo')
                    ->schema([
                        Select::make('village_logo')
                            ->label('Logo Desa')
                            ->options($mediaOptions)
                            ->searchable(),
                        Select::make('favicon')
                            ->label('Favicon')
                            ->options($mediaOptions)
                            ->searchable(),
                    ])->columns(2),
                Section::make('Alamat dan Kontak')
                    ->schema([
                        TextInput::make('contact_email')->label('Email')->email(),
                        TextInput::make('contact_phone')->label('Telepon'),
                        Textarea::make('address_street')->label('Jalan'),
                        TextInput::make('address_village')->label('Desa/Kelurahan'),
                        TextInput::make('address_subdistrict')->label('Kecamatan'),
                        TextInput::make('address_district')->label('Kabupaten/Kota'),
                        TextInput::make('address_province')->label('Provinsi'),
                        TextInput::make('address_postal_code')->label('Kode Pos'),
                    ])->columns(2),
                Section::make('Media Sosial')
                    ->schema([
                        TextInput::make('social_facebook')->label('Facebook')->url(),
                        TextInput::make('social_instagram')->label('Instagram')->url(),
                        TextInput::make('social_twitter')->label('Twitter/X')->url(),
                        TextInput::make('social_youtube')->label('YouTube')->url(),
                    ])->columns(2),
                Section::make('Tampilan')
                    ->schema([
                        TextInput::make('footer_copyright_text')->label('Teks Hak Cipta Kaki Halaman'),
                    ])->columns(1),
                Section::make('Mesin Pencari')
                    ->schema([
                        TextInput::make('meta_title')->label('Judul Utama untuk Mesin Pencari'),
                        Textarea::make('meta_description')->label('Deskripsi Utama untuk Mesin Pencari'),
                    ])->columns(1),
                Section::make('Tanda Air')
                    ->schema([
                        Toggle::make('enable_visible_watermark')->label('Aktifkan Tanda Air Terlihat'),
                        TextInput::make('watermark_text')->label('Teks Tanda Air'),
                        Select::make('watermark_image')
                            ->label('Gambar Tanda Air (Opsional)')
                            ->options($mediaOptions)
                            ->searchable(),
                        TextInput::make('watermark_opacity')->label('Opasitas (%)')->numeric()->minValue(0)->maxValue(100),
                        Select::make('watermark_position')->label('Posisi Tanda Air')->options([
                            'top-left' => 'Kiri Atas',
                            'top-right' => 'Kanan Atas',
                            'bottom-left' => 'Kiri Bawah',
                            'bottom-right' => 'Kanan Bawah',
                            'center' => 'Tengah',
                        ]),
                        TextInput::make('watermark_scale')->numeric()->minValue(1)->maxValue(100)->label('Skala (%)'),
                    ])->columns(2),
                Section::make('Batas Unggahan')
                    ->schema([
                        TextInput::make('max_upload_size')->numeric()->minValue(1)->maxValue(50)->label('Ukuran Maksimal Berkas (MB)'),
                        TextInput::make('max_image_width')->numeric()->minValue(100)->maxValue(8000)->label('Maksimal Lebar Gambar (px)'),
                        TextInput::make('max_image_height')->numeric()->minValue(100)->maxValue(8000)->label('Maksimal Tinggi Gambar (px)'),
                        TextInput::make('processing_timeout')->numeric()->minValue(10)->maxValue(600)->label('Batas Waktu Pemrosesan (detik)'),
                    ])->columns(2),
                Section::make('Notifikasi Email')
                    ->schema([
                        TextInput::make('notification_email')->label('Email Penerima Notifikasi (Opsional)')->email(),
                    ])->columns(1),
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

    public function previewAction(): \Filament\Actions\Action
    {
        return PreviewAction::make('settings', editing: true);
    }
}
