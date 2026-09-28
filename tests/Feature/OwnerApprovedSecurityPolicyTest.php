<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\EditProfile;
use App\Models\Admin;
use App\Services\AdminLoginThrottle;
use App\Support\AdminPasswordPolicy;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerApprovedSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_shared_password_policy_rejects_short_or_missing_classes(): void
    {
        foreach (['Aa123456789', 'abcdefghijkl12', 'ABCDEFGHIJK12', 'Abcdefghijkl'] as $weak) {
            $this->assertTrue(Validator::make(['password' => $weak],
                ['password' => [AdminPasswordPolicy::rule()]])->fails());
        }
        $this->assertTrue(Validator::make(['password' => 'ValidPassword12'],
            ['password' => [AdminPasswordPolicy::rule()]])->passes());
    }

    public function test_new_hashes_use_argon2id_and_existing_bcrypt_hashes_still_verify(): void
    {
        $this->assertSame(PASSWORD_ARGON2ID, password_get_info(Hash::make('ValidPassword12'))['algo']);
        $legacy = password_hash('LegacyPassword12', PASSWORD_BCRYPT);
        $this->assertTrue(Hash::check('LegacyPassword12', $legacy));
    }

    public function test_provisioning_rejects_a_weak_supplied_password(): void
    {
        $this->artisan('admin:provision', [
            '--name' => 'Test Admin', '--username' => 'admin', '--email' => 'admin@example.test',
        ])->expectsQuestion('Password (leave blank to auto-generate)', 'weakpassword')
            ->assertFailed();
        $this->assertSame(0, Admin::count());
    }

    public function test_login_throttle_uses_isolated_identity_client_keys_and_resets_on_success(): void
    {
        $this->freezeTime();
        $throttle = app(AdminLoginThrottle::class);
        $key = $throttle->key('VillageAdmin', '127.0.0.1');
        $other = $throttle->key('VillageAdmin', '127.0.0.2');
        $this->assertNotSame($key, $other);
        foreach ([1, 3, 5, 10] as $delay) {
            $throttle->failed($key);
            $this->assertSame($delay, $throttle->waitSeconds($key));
            $this->assertSame(0, $throttle->waitSeconds($other));
            $this->travel($delay)->seconds();
        }
        $throttle->failed($key);
        $this->assertSame(5, RateLimiter::attempts($key));
        $this->assertSame(900, $throttle->waitSeconds($key));
        $throttle->succeeded($key);
        $this->assertSame(0, $throttle->waitSeconds($key));
        $this->assertSame(0, RateLimiter::attempts($key));
    }

    public function test_email_change_requires_fresh_current_password_and_totp_at_save(): void
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('CurrentPassword12'),
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
        ]);
        $this->actingAs($admin);

        Livewire::test(EditProfile::class)->fillForm([
            'name' => $admin->name, 'username' => $admin->username,
            'email' => 'new-admin@example.test',
        ])->call('save')->assertHasFormErrors(['currentPassword', 'totp']);
        $this->assertSame($admin->email, $admin->fresh()->email);

        Livewire::test(EditProfile::class)->fillForm([
            'name' => $admin->name, 'username' => $admin->username,
            'email' => 'new-admin@example.test',
            'currentPassword' => 'CurrentPassword12',
            'totp' => AppAuthentication::make()->getCurrentCode($admin),
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('new-admin@example.test', $admin->fresh()->email);
    }
}
