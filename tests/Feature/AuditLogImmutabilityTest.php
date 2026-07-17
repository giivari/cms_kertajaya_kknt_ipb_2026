<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_is_immutable_via_policy()
    {
        $admin = Admin::factory()->create(['force_password_change' => false, 'app_authentication_secret' => 'JBSWY3DPEHPK3PXP']);
        $this->actingAs($admin);

        $auditLog = AuditLog::create([
            'admin_id' => $admin->id,
            'event_type' => 'test_event',
            'subject_type' => 'App\Models\Admin',
            'subject_id' => $admin->id,
            'old_values' => [],
            'new_values' => [],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
        ]);

        $this->assertTrue($admin->can('viewAny', AuditLog::class));
        $this->assertTrue($admin->can('view', $auditLog));
        $this->assertFalse($admin->can('create', AuditLog::class));
        $this->assertFalse($admin->can('update', $auditLog));
        $this->assertFalse($admin->can('delete', $auditLog));

        // Also verify the Filament resource blocks deletion
        $response = $this->delete(route('filament.admin.resources.audit-logs.index'));
        $response->assertStatus(405); // Method not allowed (no bulk delete route exists)
    }
}
