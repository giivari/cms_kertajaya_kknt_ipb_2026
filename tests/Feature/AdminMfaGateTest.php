<?php

namespace Tests\Feature;

use App\Filament\Livewire\SecureDatabaseNotifications;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\MyProfile;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Admin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMfaGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pre_mfa_admin_is_restricted_to_enrollment_and_logout_controls(): void
    {
        $admin = $this->adminWithoutMfa();
        $this->actingAs($admin, 'web');

        $setUpUrl = $this->setUpUrl();

        $this->get($setUpUrl)->assertOk();
        $this->get('/'.config('village.admin_path', 'desa-dashboard'))->assertRedirect($setUpUrl);
        $this->get(MyProfile::getUrl())->assertRedirect($setUpUrl);
        $this->get(EditProfile::getUrl())->assertRedirect($setUpUrl);
        $this->get(PageResource::getUrl('index'))->assertRedirect($setUpUrl);

        $menuItems = Filament::getCurrentPanel()->getUserMenuItems();
        $this->assertArrayNotHasKey('profile', $menuItems);
        $this->assertArrayHasKey('logout', $menuItems);
        $this->assertFalse(Filament::getCurrentPanel()->hasDatabaseNotifications());

        Livewire::actingAs($admin, 'web')
            ->test(SecureDatabaseNotifications::class)
            ->assertForbidden();
    }

    public function test_post_mfa_admin_can_use_panel_profile_notifications_and_resources(): void
    {
        $admin = Admin::factory()->create([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
            'force_password_change' => false,
        ]);
        $this->actingAs($admin, 'web');

        $this->get('/'.config('village.admin_path', 'desa-dashboard'))->assertOk();
        $this->get(MyProfile::getUrl())->assertOk();
        $this->get(EditProfile::getUrl())->assertOk();
        $this->get(PageResource::getUrl('index'))->assertOk();

        $menuItems = Filament::getCurrentPanel()->getUserMenuItems();
        $this->assertArrayHasKey('profile', $menuItems);
        $this->assertArrayHasKey('logout', $menuItems);
        $this->assertTrue(Filament::getCurrentPanel()->hasDatabaseNotifications());

        $notificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'mfa-gate-test',
            'notifiable_type' => Admin::class,
            'notifiable_id' => $admin->id,
            'data' => json_encode(['format' => 'filament']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($admin, 'web')
            ->test(SecureDatabaseNotifications::class)
            ->call('markAllNotificationsAsRead')
            ->assertHasNoErrors();

        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    private function adminWithoutMfa(): Admin
    {
        return Admin::factory()->create([
            'app_authentication_secret' => null,
            'force_password_change' => false,
        ]);
    }

    private function setUpUrl(): string
    {
        return '/'.config('village.admin_path', 'desa-dashboard').'/multi-factor-authentication/set-up';
    }
}
