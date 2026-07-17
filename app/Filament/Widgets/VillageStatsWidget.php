<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use App\Models\Media;
use App\Models\News;
use App\Models\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VillageStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Berita', News::count())
                ->description('Berita diterbitkan dan draf')
                ->icon('heroicon-o-newspaper'),
            Stat::make('Total Halaman', Page::count())
                ->description('Halaman website aktif')
                ->icon('heroicon-o-document-text'),
            Stat::make('Dokumen Publik', Document::count())
                ->description('Dokumen yang dapat diunduh')
                ->icon('heroicon-o-folder-open'),
            Stat::make('Media Tersimpan', Media::count())
                ->description('File gambar dan video')
                ->icon('heroicon-o-photo'),
        ];
    }
}
