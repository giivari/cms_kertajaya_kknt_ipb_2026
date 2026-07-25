<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\WebsiteSetting;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\PageComponent;
use App\Models\NewsCategory;
use App\Models\News;
use App\Models\GalleryAlbum;
use App\Models\GalleryAlbumItem;
use App\Models\DocumentCategory;
use App\Models\Document;
use App\Models\Media;
use App\Models\MediaDerivative;

class LocalPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->error('Local preview seeder can only be run in local environment.');
            return;
        }

        $this->command->info('Creating local preview data (Safe to rerun)...');

        $this->seedSettings();
        $this->seedMenus();
        $mediaIds = $this->seedMedia();
        $this->seedPages($mediaIds);
        $this->seedNews($mediaIds);
        $this->seedGallery($mediaIds);
        $this->seedDocuments($mediaIds);

        $this->command->info('Local preview seeded successfully.');
    }

    private function seedSettings()
    {
        WebsiteSetting::updateOrCreate(
            ['key' => 'village_name'],
            ['value' => 'Desa Kertajaya']
        );
        WebsiteSetting::updateOrCreate(
            ['key' => 'village_description'],
            ['value' => 'Mewujudkan desa yang mandiri, sejahtera, dan berbudaya.']
        );
        WebsiteSetting::updateOrCreate(
            ['key' => 'contact_email'],
            ['value' => 'info@kertajaya.desa.id']
        );
        WebsiteSetting::updateOrCreate(
            ['key' => 'theme_config'],
            [
                'value' => json_encode(['theme' => 'Village Nature', 'primary_color' => '#10b981'])
            ]
        );
    }

    private function seedMenus()
    {
        $mainMenu = Menu::firstOrCreate(
            ['location' => 'primary'],
            ['name' => 'Main Menu', 'description' => 'Primary site navigation']
        );

        $items = [
            ['label' => 'Beranda', 'custom_url' => '/', 'position' => 1],
            ['label' => 'Profil Desa', 'custom_url' => '/halaman/profil-desa', 'position' => 2],
            ['label' => 'Potensi Desa', 'custom_url' => '/halaman/potensi-desa', 'position' => 3],
            ['label' => 'Berita', 'custom_url' => '/berita', 'position' => 4],
            ['label' => 'Galeri', 'custom_url' => '/galeri', 'position' => 5],
            ['label' => 'Dokumen', 'custom_url' => '/dokumen', 'position' => 6],
        ];

        foreach ($items as $item) {
            MenuItem::firstOrCreate(
                ['menu_id' => $mainMenu->id, 'custom_url' => $item['custom_url']],
                [
                    'label' => $item['label'],
                    'position' => $item['position'],
                    'link_type' => 'custom',
                    'target' => '_self',
                    'is_visible' => true,
                ]
            );
        }
    }

    private function seedMedia(): array
    {
        $mediaIds = [];

        // Seed 3 Images
        for ($i = 1; $i <= 3; $i++) {
            $media = Media::firstOrCreate(
                ['filename' => "preview-image-{$i}.jpg"],
                [
                    'disk' => 'public',
                    'directory' => 'preview',
                    'original_filename' => "image-{$i}.jpg",
                    'mime_type' => 'image/jpeg',
                    'extension' => 'jpg',
                    'size' => 102400,
                    'processing_status' => 'completed',
                    'invisible_watermark_status' => 'verified',
                ]
            );
            MediaDerivative::firstOrCreate(
                ['media_id' => $media->id, 'derivative_type' => 'public'],
                [
                    'disk' => 'public',
                    'filename' => "preview-image-{$i}-public.jpg",
                    'mime_type' => 'image/jpeg',
                    'size' => 51200,
                ]
            );
            $mediaIds['image'][$i] = $media->id;
            
            // Dummy file for derivative
            \Illuminate\Support\Facades\Storage::disk('public')->put("preview-image-{$i}-public.jpg", 'dummy image content');
        }

        // Seed 2 PDFs
        for ($i = 1; $i <= 2; $i++) {
            $media = Media::firstOrCreate(
                ['filename' => "preview-doc-{$i}.pdf"],
                [
                    'disk' => 'local',
                    'directory' => 'preview',
                    'original_filename' => "doc-{$i}.pdf",
                    'mime_type' => 'application/pdf',
                    'extension' => 'pdf',
                    'size' => 204800,
                    'processing_status' => 'completed',
                    'invisible_watermark_status' => 'verified',
                ]
            );
            MediaDerivative::firstOrCreate(
                ['media_id' => $media->id, 'derivative_type' => 'public'],
                [
                    'disk' => 'local', // We keep derivatives on local for secure download routes for documents
                    'filename' => "preview-doc-{$i}-public.pdf",
                    'mime_type' => 'application/pdf',
                    'size' => 102400,
                ]
            );
            $mediaIds['pdf'][$i] = $media->id;
            
            \Illuminate\Support\Facades\Storage::disk('local')->put("preview-doc-{$i}-public.pdf", 'dummy pdf content');
        }

        return $mediaIds;
    }

    private function seedPages(array $mediaIds)
    {
        $pages = [
            [
                'title' => 'Profil Desa',
                'slug' => 'profil-desa',
                'content' => 'Profil resmi Desa Kertajaya.',
                'status' => 'published'
            ],
            [
                'title' => 'Visi dan Misi',
                'slug' => 'visi-dan-misi',
                'content' => 'Visi: Menjadi desa terdepan.',
                'status' => 'published'
            ],
            [
                'title' => 'Potensi Desa',
                'slug' => 'potensi-desa',
                'content' => 'Potensi pertanian dan wisata.',
                'status' => 'published'
            ],
        ];

        foreach ($pages as $p) {
            $page = Page::firstOrCreate(
                ['slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'status' => $p['status'],
                    'published_at' => now(),
                ]
            );

            // Seed Page Builder Components if not exist
            if ($page->sections()->count() === 0) {
                $section = $page->sections()->create([
                    'position' => 1, 
                    'layout_type' => 'full_width',
                    'is_visible' => true,
                    'section_settings' => []
                ]);
                
                $section->components()->create([
                    'component_type' => 'heading',
                    'position' => 1,
                    'column_position' => 1,
                    'is_visible' => true,
                    'content_data' => ['text' => $p['title'], 'level' => 'h1'],
                    'component_settings' => []
                ]);
                
                $section->components()->create([
                    'component_type' => 'rich_text',
                    'position' => 2,
                    'column_position' => 1,
                    'is_visible' => true,
                    'content_data' => ['content' => '<p>' . $p['content'] . '</p>'],
                    'component_settings' => []
                ]);
                
                $section->components()->create([
                    'component_type' => 'image',
                    'position' => 3,
                    'column_position' => 1,
                    'is_visible' => true,
                    'content_data' => ['media_id' => $mediaIds['image'][1]],
                    'component_settings' => []
                ]);
            }
        }
    }

    private function seedNews(array $mediaIds)
    {
        $category = NewsCategory::firstOrCreate(
            ['slug' => 'pengumuman'],
            ['name' => 'Pengumuman', 'description' => 'Pengumuman desa']
        );

        for ($i = 1; $i <= 3; $i++) {
            News::firstOrCreate(
                ['slug' => "berita-desa-{$i}"],
                [
                    'title' => "Berita Desa $i",
                    'content' => "Ini adalah konten berita desa ke-$i",
                    'news_category_id' => $category->id,
                    'featured_media_id' => $mediaIds['image'][$i],
                    'status' => 'published',
                    'published_at' => now()->subDays($i),
                ]
            );
        }

        News::firstOrCreate(
            ['slug' => 'berita-draft-rahasia'],
            [
                'title' => 'Berita Draft Rahasia',
                'content' => 'Ini draft, tidak boleh terlihat tamu.',
                'news_category_id' => $category->id,
                'status' => 'draft',
            ]
        );
    }

    private function seedGallery(array $mediaIds)
    {
        $album1 = GalleryAlbum::firstOrCreate(
            ['slug' => 'kegiatan-desa-1'],
            [
                'title' => 'Kegiatan Desa 1',
                'description' => 'Foto kegiatan desa pertama',
                'cover_media_id' => $mediaIds['image'][1],
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        if ($album1->items()->count() === 0) {
            $album1->items()->create(['media_id' => $mediaIds['image'][1], 'caption' => 'Foto 1', 'position' => 1]);
            $album1->items()->create(['media_id' => $mediaIds['image'][2], 'caption' => 'Foto 2', 'position' => 2]);
        }

        GalleryAlbum::firstOrCreate(
            ['slug' => 'kegiatan-desa-2'],
            [
                'title' => 'Kegiatan Desa 2',
                'cover_media_id' => $mediaIds['image'][3],
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        // Empty Album
        GalleryAlbum::firstOrCreate(
            ['slug' => 'album-kosong'],
            [
                'title' => 'Album Kosong',
                'status' => 'published',
                'published_at' => now(),
            ]
        );
    }

    private function seedDocuments(array $mediaIds)
    {
        $category = DocumentCategory::firstOrCreate(
            ['slug' => 'peraturan-desa'],
            ['name' => 'Peraturan Desa', 'description' => 'Dokumen peraturan']
        );

        for ($i = 1; $i <= 2; $i++) {
            Document::firstOrCreate(
                ['slug' => "dokumen-publik-{$i}"],
                [
                    'title' => "Dokumen Publik $i",
                    'description' => "Deskripsi dokumen $i",
                    'document_category_id' => $category->id,
                    'file_media_id' => $mediaIds['pdf'][$i],
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );
        }

        Document::firstOrCreate(
            ['slug' => 'dokumen-draft'],
            [
                'title' => 'Dokumen Draft Rahasia',
                'document_category_id' => $category->id,
                'file_media_id' => $mediaIds['pdf'][1], // Use same media for draft
                'status' => 'draft',
            ]
        );
    }
}
