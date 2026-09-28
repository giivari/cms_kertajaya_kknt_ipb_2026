<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class P2SingleAdminConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_supported_provisioning_creates_at_most_one_admin(): void
    {
        $password = Str::password(32);
        $first = $this->provisioningProcess('p2-admin-first', 'p2-admin-first@example.test', $password);
        $second = $this->provisioningProcess('p2-admin-second', 'p2-admin-second@example.test', $password);

        $first->start();
        $second->start();
        $first->wait();
        $second->wait();

        $exitCodes = [$first->getExitCode(), $second->getExitCode()];
        sort($exitCodes);

        $this->assertSame([0, 1], $exitCodes);
        $this->assertSame(1, Admin::withTrashed()->count());
        $this->assertTrue(
            str_contains($first->getOutput().$second->getOutput(), 'Administrator account provisioned successfully.')
        );
    }

    public function test_anomalous_multi_admin_state_is_reported_without_deletion(): void
    {
        foreach (['p2-anomaly-one', 'p2-anomaly-two'] as $username) {
            DB::table('admins')->insert([
                'id' => (string) Str::uuid(),
                'name' => $username,
                'username' => $username,
                'email' => "{$username}@example.test",
                'password' => 'test-only-hash',
                'force_password_change' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $process = $this->provisioningProcess('p2-admin-third', 'p2-admin-third@example.test', Str::password(32));
        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('Administrator data is anomalous', $process->getOutput().$process->getErrorOutput());
        $this->assertSame(2, Admin::withTrashed()->count());
    }

    private function provisioningProcess(string $username, string $email, string $password): Process
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'admin:provision',
            '--name=P2 Test Administrator',
            "--username={$username}",
            "--email={$email}",
        ], base_path(), ['APP_ENV' => 'testing']);
        $process->setInput("{$password}\n");
        $process->setTimeout(20);

        return $process;
    }
}
