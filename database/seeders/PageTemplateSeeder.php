<?php

namespace Database\Seeders;

use App\Enums\ComponentType;
use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\PageComponent;
use App\Models\PageSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Profil Desa',
            'Sejarah Desa',
            'Visi dan Misi',
            'Potensi Desa',
            'Informasi Dusun',
            'Pelayanan',
            'BUMDes',
            'Rawan Bencana',
        ];

        foreach ($templates as $template) {
            if (Page::where('title', $template)->exists()) {
                continue;
            }

            $page = Page::create([
                'title' => $template,
                'slug' => Str::slug($template),
                'status' => PageStatus::DRAFT->value,
                'is_featured' => false,
            ]);

            $section = PageSection::create([
                'page_id' => $page->id,
                'name' => 'Main Content',
                'layout_type' => 'single_column',
                'position' => 0,
            ]);

            PageComponent::create([
                'page_section_id' => $section->id,
                'component_type' => ComponentType::HEADING->value,
                'position' => 0,
                'content_data' => [
                    'text' => $template,
                    'level' => 'h1',
                    'alignment' => 'left',
                ],
            ]);

            PageComponent::create([
                'page_section_id' => $section->id,
                'component_type' => ComponentType::RICH_TEXT->value,
                'position' => 1,
                'content_data' => [
                    'content' => '<p>Deskripsi untuk ' . $template . ' dapat ditulis di sini.</p>',
                ],
            ]);
        }
    }
}
