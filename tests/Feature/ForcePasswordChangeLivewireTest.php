<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\WebsiteSettings;
use App\Models\Admin;
use App\Models\WebsiteSetting;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use Tests\TestCase;

class ForcePasswordChangeLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function signedSnapshot(string $url, string $component): string
    {
        $response = $this->get($url)->assertOk();
        preg_match_all('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);

        foreach ($matches[1] as $encoded) {
            $snapshot = html_entity_decode($encoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ((json_decode($snapshot, true)['memo']['name'] ?? null) === $component) {
                return $snapshot;
            }
        }

        $this->fail('The real page did not contain the expected signed Livewire snapshot.');
    }

    private function update(string $snapshot, array $updates = [], array $calls = [])
    {
        return $this->withHeader('X-Livewire', 'true')->postJson(
            app(HandleRequests::class)->getUpdateUri(),
            ['components' => [['snapshot' => $snapshot, 'updates' => $updates, 'calls' => $calls]]],
        );
    }

    public function test_open_settings_page_cannot_save_after_force_password_change_is_enabled(): void
    {
        $admin = Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]);
        $this->actingAs($admin);
        $snapshot = $this->signedSnapshot(WebsiteSettings::getUrl(), 'app.filament.pages.website-settings');

        $admin->update(['force_password_change' => true]);
        Auth::guard('web')->setUser($admin->fresh());

        $this->update($snapshot, ['data.village_name' => 'FORBIDDEN_UPDATE'], [
            ['method' => 'save', 'params' => []],
        ])->assertRedirect(EditProfile::getUrl());
        $this->assertNotSame('FORBIDDEN_UPDATE', WebsiteSetting::find('village_name')?->value);
    }

    public function test_profile_remains_available_during_force_password_change_transition(): void
    {
        $admin = Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]);
        $this->actingAs($admin);
        $snapshot = $this->signedSnapshot(EditProfile::getUrl(), 'app.filament.pages.auth.edit-profile');

        $admin->update(['force_password_change' => true]);
        Auth::guard('web')->setUser($admin->fresh());

        $this->update($snapshot)->assertOk();
        $this->get(EditProfile::getUrl())->assertOk();
    }
}
