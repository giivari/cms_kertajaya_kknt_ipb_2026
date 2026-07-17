<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScreenVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_screens_load_successfully()
    {
        $admin = Admin::factory()->create(['force_password_change' => false, 'app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin)->withSession(['session_created_at' => time()]);

        $routes = [
            'filament.admin.pages.dashboard',
            'filament.admin.pages.website-settings',
            'filament.admin.resources.media.index',
            'filament.admin.resources.pages.index',
            'filament.admin.resources.pages.create',
            'filament.admin.resources.menus.index',
            'filament.admin.resources.news.index',
            'filament.admin.resources.news-categories.index',
            'filament.admin.resources.gallery-albums.index',
            'filament.admin.resources.documents.index',
            'filament.admin.resources.document-categories.index',
            'filament.admin.resources.audit-logs.index',
        ];

        foreach ($routes as $route) {
            $response = $this->get(route($route));
            $response->assertSuccessful();
        }
    }
}
