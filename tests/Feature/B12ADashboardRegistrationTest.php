<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use Filament\Facades\Filament;
use Tests\TestCase;

class B12ADashboardRegistrationTest extends TestCase
{
    public function test_custom_dashboard_is_registered(): void
    {
        $pages = Filament::getPanel('admin')->getPages();

        $this->assertContains(
            Dashboard::class,
            $pages,
            'The B1.2A custom dashboard must remain registered.'
        );

        $this->assertNotContains(
            \Filament\Pages\Dashboard::class,
            $pages,
            'The default Filament dashboard must remain excluded.'
        );
    }

    public function test_dashboard_widgets_are_registered(): void
    {
        $widgets = Filament::getPanel('admin')->getWidgets();

        $this->assertContains(
            QuickActionsWidget::class,
            $widgets,
            'QuickActionsWidget must remain registered.'
        );

        $this->assertContains(
            RecentActivityWidget::class,
            $widgets,
            'RecentActivityWidget must remain registered.'
        );
    }

    public function test_quick_actions_view_preserves_expected_actions(): void
    {
        $viewPath = resource_path(
            'views/filament/widgets/quick-actions-widget.blade.php'
        );

        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        $this->assertIsString($content);

        foreach ([
            'Aksi Cepat',
            'Buat Berita',
            'Buat Halaman',
            'Unggah Media',
            'Buat Album',
            'Tambah Dokumen',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $content);
        }

        $this->assertStringContainsString(
            'text-gray-950 dark:text-white',
            $content,
            'The quick actions heading must support light and dark themes.'
        );
    }

    public function test_recent_activity_heading_is_preserved(): void
    {
        $widgetPath = app_path(
            'Filament/Widgets/RecentActivityWidget.php'
        );

        $this->assertFileExists($widgetPath);

        $content = file_get_contents($widgetPath);

        $this->assertIsString($content);
        $this->assertStringContainsString(
            'Aktivitas Terbaru',
            $content
        );
    }
}
