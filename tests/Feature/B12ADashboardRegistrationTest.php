<?php

namespace Tests\Feature;

use App\Filament\AvatarProviders\LocalInitialsAvatarProvider;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\SystemAlertWidget;
use App\Filament\Widgets\VillageStatsWidget;
use App\Filament\Widgets\WelcomeHeaderWidget;
use App\Models\Admin;
use App\Models\AuditLog;
use DateTimeInterface;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Filament\Widgets\AccountWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class B12ADashboardRegistrationTest extends TestCase
{
    use RefreshDatabase;

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
            VillageStatsWidget::class,
            $widgets,
            'VillageStatsWidget must remain registered.'
        );

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

        $this->assertNotContains(
            WelcomeHeaderWidget::class,
            $widgets,
            'The greeting is rendered by the Dashboard header, not a duplicate widget.'
        );

        $this->assertNotContains(
            AccountWidget::class,
            $widgets,
            'AccountWidget must not duplicate the dashboard greeting.'
        );

        $this->assertNotContains(
            SystemAlertWidget::class,
            $widgets,
            'A development-only system alert must not appear on the village dashboard.'
        );
    }

    public function test_dashboard_renders_four_real_statistic_labels(): void
    {
        $admin = Admin::factory()->create();

        Livewire::actingAs($admin)
            ->test(VillageStatsWidget::class)
            ->assertSee('Halaman Diterbitkan')
            ->assertSee('Berita Diterbitkan')
            ->assertSee('Album Galeri')
            ->assertSee('Dokumen Publik');
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
            'Buat Berita',
            'Buat Halaman',
            'Unggah Media',
            'Tambah Dokumen',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $content);
        }

        $this->assertSame(4, substr_count($content, 'class="admin-quick-action"'));
    }

    public function test_recent_activity_heading_is_preserved(): void
    {
        $viewPath = resource_path(
            'views/filament/widgets/recent-activity-widget.blade.php'
        );

        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        $this->assertIsString($content);
        $this->assertStringContainsString(
            'Aktivitas Terbaru',
            $content
        );
    }

    public function test_dashboard_renders_recent_activity_with_a_persisted_audit_log_without_mutating_it(): void
    {
        $admin = Admin::factory()->create([
            'force_password_change' => false,
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        $activity = AuditLog::create([
            'admin_id' => $admin->id,
            'event_type' => 'created',
            'subject_type' => 'App\\Models\\News',
            'subject_id' => 'dashboard-regression',
            'old_values' => null,
            'new_values' => ['title' => 'Berita Dashboard'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Dashboard regression test',
        ])->refresh();

        $rawCreatedAt = DB::table('audit_logs')->where('id', $activity->id)->value('created_at');
        $auditCount = AuditLog::count();

        $this->assertNotNull($rawCreatedAt);
        $this->assertInstanceOf(DateTimeInterface::class, $activity->created_at);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertStatus(200)
            ->assertSee('Selamat Datang, Test Admin');

        Livewire::actingAs($admin)
            ->test(RecentActivityWidget::class)
            ->assertSee('Aktivitas Terbaru')
            ->assertSee('Membuat berita')
            ->assertSee('Berita Dashboard')
            ->assertSee($admin->name);

        $this->assertSame($auditCount, AuditLog::count());
        $this->assertSame(
            $rawCreatedAt,
            DB::table('audit_logs')->where('id', $activity->id)->value('created_at')
        );
    }

    public function test_panel_keeps_official_shell_behaviors_and_local_avatar(): void
    {
        $admin = Admin::factory()->create();
        $panel = Filament::getPanel('admin');

        Filament::setCurrentPanel($panel);
        $this->actingAs($admin);

        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertTrue($panel->hasUserMenu());
        $this->assertSame(EditProfile::class, $panel->getProfilePage());
        $this->assertSame(LocalInitialsAvatarProvider::class, $panel->getDefaultAvatarProvider());
        $this->assertArrayHasKey('profile', $panel->getUserMenuItems());
        $this->assertArrayHasKey('logout', $panel->getUserMenuItems());

        $avatarSource = file_get_contents(app_path('Filament/AvatarProviders/LocalInitialsAvatarProvider.php'));
        $this->assertIsString($avatarSource);
        $this->assertStringNotContainsString('ui-avatars.com', $avatarSource);
    }

    public function test_sidebar_uses_official_desktop_toggle_aliases_without_replacing_mobile_drawer_icons(): void
    {
        $icons = Filament::getPanel('admin')->getIcons();

        foreach ([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON,
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL,
        ] as $alias) {
            $this->assertArrayHasKey($alias, $icons);
            $this->assertSame(Heroicon::OutlinedBars3, $icons[$alias]);
        }

        $this->assertArrayNotHasKey(PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON, $icons);
        $this->assertArrayNotHasKey(PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON, $icons);
    }
}
