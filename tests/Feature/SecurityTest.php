<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_admin_cannot_be_provisioned_or_created()
    {
        Admin::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only one administrator account is permitted.');

        Admin::factory()->create(['username' => 'admin2']);
    }

    public function test_provision_command_fails_if_admin_exists()
    {
        Admin::factory()->create();

        $this->artisan('admin:provision', [
            '--password' => 'secret',
        ])->assertFailed();
    }

    public function test_audit_logs_redact_sensitive_information()
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('secretpassword123'),
        ]);

        $admin->update([
            'password' => Hash::make('newsecret456'),
        ]);

        $logs = AuditLog::all();
        $this->assertTrue($logs->count() > 0);

        foreach ($logs as $log) {
            $old = json_encode($log->old_values);
            $new = json_encode($log->new_values);

            $this->assertStringNotContainsString('secretpassword123', $old ?: '');
            $this->assertStringNotContainsString('secretpassword123', $new ?: '');
            $this->assertStringNotContainsString('newsecret456', $old ?: '');
            $this->assertStringNotContainsString('newsecret456', $new ?: '');
        }
    }

    public function test_turnstile_validation_fails_on_missing_token()
    {
        $admin = Admin::factory()->create();

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => '',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['captcha' => 'required']);
    }

    public function test_turnstile_validation_fails_on_invalid_token()
    {
        $admin = Admin::factory()->create();

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false], 200),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => $admin->username,
                'password' => 'password',
                'captcha' => 'invalid-token',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['captcha']);
    }
}
