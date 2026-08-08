<?php

namespace App\Filament\Pages;

use App\Models\Media;
use App\Filament\Support\PreviewAction;
use App\Services\SettingsService;
use Filament\Schemas\Components\Fieldset;
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
            'footer_text' => SettingsService::get('footer_text', '© ' . date('Y') . ' Desa Kertajaya. Hak cipta dilindungi.'),
            'footer_link_1_label' => SettingsService::get('footer_link_1_label', ''),
            'footer_link_1_url' => SettingsService::get('footer_link_1_url', ''),
            'footer_link_2_label' => SettingsService::get('footer_link_2_label', ''),
            'footer_link_2_url' => SettingsService::get('footer_link_2_url', ''),
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
            // BERANDA SETTINGS
            'hero_title' => SettingsService::get('hero_title', 'Desa yang Tumbuh Bersama Masyarakat'),
            'hero_description' => SettingsService::get('hero_description', 'Portal informasi resmi Desa Kertajaya yang menghadirkan informasi, pelayanan, potensi, dan perkembangan desa secara terbuka untuk seluruh masyarakat.'),
            'hero_image' => SettingsService::get('hero_image', null),
            'profil_title' => SettingsService::get('profil_title', 'Keindahan Alam dan Harmoni Masyarakat'),
            'profil_description' => SettingsService::get('profil_description', 'Desa Kertajaya terletak di dataran tinggi yang dikelilingi oleh perbukitan hijau dan hamparan sawah yang subur. Masyarakat kami hidup berdampingan dengan alam, memelihara tradisi luhur sambil terus bergerak maju mengikuti perkembangan zaman.'),
            'profil_image_1' => SettingsService::get('profil_image_1', null),
            'profil_image_2' => SettingsService::get('profil_image_2', null),
            'potensi_title' => SettingsService::get('potensi_title', 'Kekayaan Alam dan Karya Masyarakat'),
            'potensi_description' => SettingsService::get('potensi_description', 'Mengenali lebih dekat sumber daya alam dan kreativitas warga yang menjadi motor penggerak kesejahteraan desa.'),
            'potensi_1_title' => SettingsService::get('potensi_1_title', 'Pertanian & Perkebunan'),
            'potensi_1_desc' => SettingsService::get('potensi_1_desc', 'Hamparan sawah terasering dan perkebunan teh yang menjadi tulang punggung ekonomi warga.'),
            'potensi_1_image' => SettingsService::get('potensi_1_image', null),
            'potensi_1_link' => SettingsService::get('potensi_1_link', ''),
            'potensi_2_title' => SettingsService::get('potensi_2_title', 'Pariwisata Alam'),
            'potensi_2_desc' => SettingsService::get('potensi_2_desc', 'Destinasi wisata curug dan desa wisata yang asri.'),
            'potensi_2_image' => SettingsService::get('potensi_2_image', null),
            'potensi_2_link' => SettingsService::get('potensi_2_link', ''),
            'potensi_3_title' => SettingsService::get('potensi_3_title', 'UMKM Lokal'),
            'potensi_3_desc' => SettingsService::get('potensi_3_desc', 'Kerajinan bambu dan olahan makanan tradisional.'),
            'potensi_3_image' => SettingsService::get('potensi_3_image', null),
            'potensi_3_link' => SettingsService::get('potensi_3_link', ''),
            'potensi_all_link' => SettingsService::get('potensi_all_link', ''),
            'stat_population' => SettingsService::get('stat_population', '3.450'),
            'stat_families' => SettingsService::get('stat_families', '850'),
            'stat_area' => SettingsService::get('stat_area', '1.250'),
            'stat_hamlets' => SettingsService::get('stat_hamlets', '4'),
        ]);
    }

    public static function getLinkSchema(string $prefix, string $label): array
    {
        return [
            \Filament\Forms\Components\Select::make("{$prefix}_type")
                ->label("Tujuan Tautan {$label}")
                ->options([
                    'none' => 'Sembunyikan Tombol / Tanpa Tautan',
                    \App\Enums\LinkType::PAGE->value => 'Halaman Website',
                    \App\Enums\LinkType::HOME->value => 'Beranda',
                    \App\Enums\LinkType::NEWS_INDEX->value => 'Daftar Berita',
                    \App\Enums\LinkType::GALLERY_INDEX->value => 'Daftar Galeri',
                    \App\Enums\LinkType::DOCUMENT_INDEX->value => 'Daftar Dokumen',
                    \App\Enums\LinkType::MAP->value => 'Peta',
                    \App\Enums\LinkType::CONTACT->value => 'Kontak',
                    \App\Enums\LinkType::CUSTOM->value => 'Tautan Luar / Kustom',
                ])
                ->default('none')
                ->live()
                ->required()
                ->columnSpan(fn ($get) => in_array($get("{$prefix}_type"), [\App\Enums\LinkType::PAGE->value, \App\Enums\LinkType::CUSTOM->value]) ? 1 : 2),
            \Filament\Forms\Components\Select::make("{$prefix}_page_id")
                ->label("Halaman yang Dituju ({$label})")
                ->options(fn () => \App\Models\Page::where('status', \App\Enums\PageStatus::PUBLISHED->value)->pluck('title', 'id'))
                ->searchable()
                ->visible(fn ($get) => $get("{$prefix}_type") === \App\Enums\LinkType::PAGE->value)
                ->required(fn ($get) => $get("{$prefix}_type") === \App\Enums\LinkType::PAGE->value)
                ->columnSpan(1),
            \Filament\Forms\Components\TextInput::make("{$prefix}_custom_url")
                ->label("Alamat Tautan ({$label})")
                ->placeholder('Contoh: /halaman/potensi-desa atau #profil-desa')
                ->visible(fn ($get) => $get("{$prefix}_type") === \App\Enums\LinkType::CUSTOM->value)
                ->required(fn ($get) => $get("{$prefix}_type") === \App\Enums\LinkType::CUSTOM->value)
                ->live(onBlur: true)
                ->columnSpan(1),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $mediaOptions = fn () => Media::where('invisible_watermark_status', 'verified')
            ->pluck('original_filename', 'id');

        return $schema
            ->schema([
                \Filament\Schemas\Components\Tabs::make('Pengaturan')
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('Identitas')
                            ->schema([
                                Section::make('Identitas Desa')
                                    ->schema([
                                        TextInput::make('village_name')->label('Nama Desa')->required()->live(onBlur: true),
                                        Textarea::make('village_description')->label('Deskripsi Singkat')->live(debounce: 500),
                                    ])->columns(2),
                                Section::make('Media dan Logo')
                                    ->schema([
                                        Select::make('village_logo')
                                            ->label('Logo Desa')
                                            ->options($mediaOptions)
                                            ->searchable()
                                            ->live(),
                                        Select::make('favicon')
                                            ->label('Favicon')
                                            ->options($mediaOptions)
                                            ->searchable()
                                            ->live(),
                                    ])->columns(2),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Beranda')
                            ->schema([
                                Section::make('Bagian Pahlawan (Hero)')
                                    ->schema([
                                        TextInput::make('hero_title')->label('Judul Hero')->required()->live(onBlur: true),
                                        Textarea::make('hero_description')->label('Deskripsi Hero')->required()->live(debounce: 500),
                                        Select::make('hero_image')->label('Gambar Latar Hero')->options($mediaOptions)->searchable()->live(),
                                        Fieldset::make('Tombol Kiri Hero (opsional)')
                                            ->schema(self::getLinkSchema('hero_button_1', 'Tombol Kiri Hero'))
                                            ->columns(2),
                                        Fieldset::make('Tombol Kanan Hero (opsional)')
                                            ->schema(self::getLinkSchema('hero_button_2', 'Tombol Kanan Hero'))
                                            ->columns(2),
                                    ])->columns(1),
                                Section::make('Profil Singkat')
                                    ->schema([
                                        TextInput::make('profil_title')->label('Judul Profil')->live(onBlur: true),
                                        Textarea::make('profil_description')->label('Deskripsi Profil')->live(debounce: 500),
                                        Select::make('profil_image_1')->label('Gambar Profil 1')->options($mediaOptions)->searchable()->live(),
                                        Select::make('profil_image_2')->label('Gambar Profil 2')->options($mediaOptions)->searchable()->live(),
                                        Fieldset::make('Tombol Selengkapnya')
                                            ->schema(self::getLinkSchema('profil_button', 'Selengkapnya'))
                                            ->columns(2),
                                    ])->columns(2),
                                Section::make('Potensi Desa')
                                    ->schema([
                                        TextInput::make('potensi_title')->label('Judul Utama Bagian Potensi')->columnSpanFull()->live(onBlur: true),
                                        Textarea::make('potensi_description')->label('Deskripsi Singkat Bagian Potensi')->columnSpanFull()->live(debounce: 500),
                                        Fieldset::make('Tombol "Lihat Semua Potensi"')
                                            ->schema(self::getLinkSchema('potensi_all', 'Semua Potensi'))
                                            ->columns(2)
                                            ->columnSpanFull(),
                                        
                                        Fieldset::make('Kartu Potensi 1 (Besar)')
                                            ->schema([
                                                TextInput::make('potensi_1_title')->label('Judul')->required()->live(onBlur: true),
                                                Textarea::make('potensi_1_desc')->label('Deskripsi Singkat')->required()->columnSpanFull()->live(debounce: 500),
                                                Select::make('potensi_1_image')->label('Gambar Utama')->options($mediaOptions)->searchable()->columnSpanFull()->live(),
                                                ...self::getLinkSchema('potensi_1', 'Kartu 1'),
                                            ])->columns(2),

                                        Fieldset::make('Kartu Potensi 2 (Kecil Atas)')
                                            ->schema([
                                                TextInput::make('potensi_2_title')->label('Judul')->required()->live(onBlur: true),
                                                Textarea::make('potensi_2_desc')->label('Deskripsi Singkat')->required()->columnSpanFull()->live(debounce: 500),
                                                Select::make('potensi_2_image')->label('Gambar Utama')->options($mediaOptions)->searchable()->columnSpanFull()->live(),
                                                ...self::getLinkSchema('potensi_2', 'Kartu 2'),
                                            ])->columns(2),

                                        Fieldset::make('Kartu Potensi 3 (Kecil Bawah)')
                                            ->schema([
                                                TextInput::make('potensi_3_title')->label('Judul')->required()->live(onBlur: true),
                                                Textarea::make('potensi_3_desc')->label('Deskripsi Singkat')->required()->columnSpanFull()->live(debounce: 500),
                                                Select::make('potensi_3_image')->label('Gambar Utama')->options($mediaOptions)->searchable()->columnSpanFull()->live(),
                                                ...self::getLinkSchema('potensi_3', 'Kartu 3'),
                                            ])->columns(2),
                                    ])->columns(1),
                                Section::make('Statistik Desa')
                                    ->schema([
                                        TextInput::make('stat_population')->label('Jumlah Penduduk')->live(onBlur: true),
                                        TextInput::make('stat_families')->label('Kepala Keluarga')->live(onBlur: true),
                                        TextInput::make('stat_area')->label('Luas Wilayah (Ha)')->live(onBlur: true),
                                        TextInput::make('stat_hamlets')->label('Jumlah Dusun')->live(onBlur: true),
                                    ])->columns(2),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Alamat & Kontak')
                            ->schema([
                                Section::make('Alamat dan Kontak')
                                    ->schema([
                                        TextInput::make('contact_email')->label('Email')->email()->live(onBlur: true),
                                        TextInput::make('contact_phone')->label('Telepon')->live(onBlur: true),
                                        Textarea::make('address_street')->label('Jalan')->live(debounce: 500),
                                        TextInput::make('address_village')->label('Desa/Kelurahan')->live(onBlur: true),
                                        TextInput::make('address_subdistrict')->label('Kecamatan')->live(onBlur: true),
                                        TextInput::make('address_district')->label('Kabupaten/Kota')->live(onBlur: true),
                                        TextInput::make('address_province')->label('Provinsi')->live(onBlur: true),
                                        TextInput::make('address_postal_code')->label('Kode Pos')->live(onBlur: true),
                                    ])->columns(2),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Media Sosial')
                            ->schema([
                                Section::make('Media Sosial')
                                    ->schema([
                                        TextInput::make('social_facebook')->label('Facebook')->url()->live(onBlur: true),
                                        TextInput::make('social_instagram')->label('Instagram')->url()->live(onBlur: true),
                                        TextInput::make('social_twitter')->label('Twitter/X')->url()->live(onBlur: true),
                                        TextInput::make('social_youtube')->label('YouTube')->url()->live(onBlur: true),
                                    ])->columns(2),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Tampilan')
                            ->schema([
                                Section::make('Tampilan Dasar')
                                    ->schema([
                                        TextInput::make('footer_text')
                                            ->label('Teks Utama Kaki Halaman')
                                            ->maxLength(150)
                                            ->helperText('Contoh: © 2026 Desa Kertajaya. Hak cipta dilindungi.')
                                            ->live(onBlur: true),
                                        Fieldset::make('Tautan Ekstra 1')
                                            ->schema([
                                                TextInput::make('footer_link_1_label')->label('Teks Tautan')->maxLength(30)->live(onBlur: true),
                                                TextInput::make('footer_link_1_url')->label('URL Tautan')->placeholder('/halaman/...')->live(onBlur: true),
                                            ])->columns(2),
                                        Fieldset::make('Tautan Ekstra 2')
                                            ->schema([
                                                TextInput::make('footer_link_2_label')->label('Teks Tautan')->maxLength(30)->live(onBlur: true),
                                                TextInput::make('footer_link_2_url')->label('URL Tautan')->placeholder('/halaman/...')->live(onBlur: true),
                                            ])->columns(2),
                                    ])->columns(1),
                                Section::make('Tanda Air')
                                    ->schema([
                                        Toggle::make('enable_visible_watermark')->label('Aktifkan Tanda Air Terlihat')->live(),
                                        TextInput::make('watermark_text')->label('Teks Tanda Air')->live(onBlur: true),
                                        Select::make('watermark_image')
                                            ->label('Gambar Tanda Air (Opsional)')
                                            ->options($mediaOptions)
                                            ->searchable()
                                            ->live(),
                                        TextInput::make('watermark_opacity')->label('Opasitas (%)')->numeric()->minValue(0)->maxValue(100)->live(onBlur: true),
                                        Select::make('watermark_position')->label('Posisi Tanda Air')->options([
                                            'top-left' => 'Kiri Atas',
                                            'top-right' => 'Kanan Atas',
                                            'bottom-left' => 'Kiri Bawah',
                                            'bottom-right' => 'Kanan Bawah',
                                            'center' => 'Tengah',
                                        ])->live(),
                                        TextInput::make('watermark_scale')->numeric()->minValue(1)->maxValue(100)->label('Skala (%)')->live(onBlur: true),
                                    ])->columns(2),
                            ]),
                        \Filament\Schemas\Components\Tabs\Tab::make('Pengaturan Lanjutan')
                            ->schema([
                                Section::make('Mesin Pencari')
                                    ->schema([
                                        TextInput::make('meta_title')->label('Judul Utama untuk Mesin Pencari')->live(onBlur: true),
                                        Textarea::make('meta_description')->label('Deskripsi Utama untuk Mesin Pencari')->live(debounce: 500),
                                    ])->columns(1),
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
                            ]),
                    ])->columnSpanFull(),
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