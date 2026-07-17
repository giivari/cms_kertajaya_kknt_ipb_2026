<?php

namespace App\Filament\Widgets;

use App\Enums\MediaProcessingStatus;
use App\Enums\PageStatus;
use App\Models\Document;
use App\Models\GalleryAlbum;
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
            Stat::make('Halaman Diterbitkan', Page::where('status', PageStatus::PUBLISHED->value)->count())
                ->description('Halaman yang dapat diakses publik')
                ->icon('heroicon-o-document-check'),
            Stat::make('Halaman Draf', Page::where('status', PageStatus::DRAFT->value)->count())
                ->description('Halaman yang belum diterbitkan')
                ->icon('heroicon-o-document'),
            Stat::make('Berita Diterbitkan', News::where('status', PageStatus::PUBLISHED->value)->count())
                ->description('Artikel berita yang dapat diakses publik')
                ->icon('heroicon-o-newspaper'),
            Stat::make('Album Galeri', GalleryAlbum::count())
                ->description('Total album foto desa')
                ->icon('heroicon-o-camera'),
            Stat::make('Dokumen Publik', Document::where('status', PageStatus::PUBLISHED->value)->count())
                ->description('Dokumen yang dapat diunduh publik')
                ->icon('heroicon-o-document-duplicate'),
            Stat::make('Media Gagal Diproses', Media::where('processing_status', MediaProcessingStatus::FAILED->value)->count())
                ->description('File media yang gagal diverifikasi')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
