<?php

namespace App\Services;

use Illuminate\Support\Str;

class PageTemplateService
{
    public function getAvailableTemplates(): array
    {
        return [
            'blank' => 'Halaman Kosong',
            'profil_desa' => 'Profil Desa',
            'sejarah_desa' => 'Sejarah Desa',
            'visi_misi' => 'Visi dan Misi',
            'potensi_desa' => 'Potensi Desa',
            'informasi_dusun' => 'Informasi Dusun',
            'pelayanan' => 'Pelayanan',
            'bumdes' => 'BUMDes',
            'rawan_bencana' => 'Rawan Bencana',
        ];
    }

    public function getTemplateDefinition(string $templateKey): array
    {
        if ($templateKey === 'blank') {
            return [];
        }

        $titleMap = $this->getAvailableTemplates();
        $title = $titleMap[$templateKey] ?? 'Halaman Baru';

        return [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Bagian Judul',
                'layout_type' => 'single_column',
                'is_visible' => true,
                'components' => [
                    (string) Str::uuid() => [
                        'type' => 'heading',
                        'data' => [
                            'text' => $title,
                            'level' => 'h1',
                            'alignment' => 'center',
                            'is_visible' => true,
                        ],
                    ],
                ],
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Isi Utama',
                'layout_type' => 'single_column',
                'is_visible' => true,
                'components' => [
                    (string) Str::uuid() => [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<p>Konten '.strtolower($title).' dapat diubah di sini.</p>',
                            'is_visible' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
