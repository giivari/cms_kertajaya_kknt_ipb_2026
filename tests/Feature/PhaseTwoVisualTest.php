<?php

namespace Tests\Feature;

use App\Filament\Widgets\VillageStatsWidget;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoVisualTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_homepage_loads_typography_and_colors()
    {
        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('Bricolage+Grotesque');
        $response->assertSee('Poppins');
        $response->assertSee('bg-navy-900');
        $response->assertSee('bg-teal-600');
    }

    public function test_admin_screens_include_translated_terms()
    {
        $admin = Admin::factory()->create([
            'force_password_change' => false,
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);

        $response = $this->get(route('filament.admin.pages.dashboard'));
        $response->assertSuccessful();

        // Filament natively translates to ID based on APP_LOCALE=id
        // But we explicitly set the navigation labels
        $response->assertSee('Dasbor');
        $response->assertSee('Pengaturan Website');
        $response->assertSee('Kategori Berita');
    }

    public function test_admin_dashboard_includes_village_stats_widget()
    {
        $admin = Admin::factory()->create([
            'force_password_change' => false,
            'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
        ]);
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);

        \Livewire\Livewire::test(\App\Filament\Widgets\VillageStatsWidget::class)
            ->assertSee('Halaman Diterbitkan')
            ->assertSee('Halaman Draf')
            ->assertSee('Berita Diterbitkan')
            ->assertSee('Album Galeri')
            ->assertSee('Dokumen Publik')
            ->assertSee('Media Gagal Diproses');
    }
}
