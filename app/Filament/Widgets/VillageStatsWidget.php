<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\GalleryAlbum;
use App\Models\News;
use App\Models\Page;
use Filament\Widgets\Widget;

class VillageStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.village-stats-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'stats' => [
                [
                    'label' => 'Halaman Diterbitkan',
                    'value' => Page::published()->count(),
                    'description' => 'Dapat diakses pengunjung',
                    'icon' => 'heroicon-o-document-text',
                    'tone' => 'teal',
                ],
                [
                    'label' => 'Berita Diterbitkan',
                    'value' => News::published()->count(),
                    'description' => 'Tayang di kanal berita',
                    'icon' => 'heroicon-o-newspaper',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Album Galeri',
                    'value' => GalleryAlbum::count(),
                    'description' => 'Koleksi dokumentasi desa',
                    'icon' => 'heroicon-o-photo',
                    'tone' => 'yellow',
                ],
                [
                    'label' => 'Dokumen Diterbitkan',
                    'value' => Document::published()->count(),
                    'description' => 'Berstatus terbit',
                    'icon' => 'heroicon-o-folder-open',
                    'tone' => 'navy',
                ],
            ],
        ];
    }
}
