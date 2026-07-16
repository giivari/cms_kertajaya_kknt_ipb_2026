<?php

namespace Tests\Feature;

use App\Filament\Pages\WebsiteSettings as WebsiteSettingsPage;
use App\Models\Admin;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_settings_can_be_updated()
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'web');

        Livewire::test(WebsiteSettingsPage::class)
            ->fillForm([
                'village_name' => 'New Village Name',
                'village_description' => 'A test description',
                'contact_email' => 'contact@example.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('New Village Name', SettingsService::get('village_name'));
        $this->assertEquals('contact@example.com', SettingsService::get('contact_email'));
    }

    public function test_website_settings_cache_invalidation()
    {
        SettingsService::set('village_name', 'Old Name');
        $this->assertEquals('Old Name', SettingsService::get('village_name'));

        SettingsService::set('village_name', 'New Name');
        $this->assertEquals('New Name', SettingsService::get('village_name'));
    }
}
