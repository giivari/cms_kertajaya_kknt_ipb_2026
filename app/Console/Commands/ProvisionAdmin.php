<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Support\AdminPasswordPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProvisionAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:provision {--name=} {--username=} {--email=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision the initial administrator account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $existingCount = Admin::withTrashed()->count();
        if ($existingCount > 1) {
            $this->error('Administrator data is anomalous: more than one account exists. Provisioning is blocked until this is reviewed.');

            return 1;
        }

        if ($existingCount === 1) {
            $this->error('An administrator account already exists. Provisioning is disabled.');

            return 1;
        }

        $name = $this->option('name') ?? $this->ask('Administrator Name', 'Village Administrator');
        $username = $this->option('username') ?? $this->ask('Username', 'admin');
        $email = $this->option('email') ?? $this->ask('Email Address', 'admin@example.com');

        $password = $this->secret('Password (leave blank to auto-generate)');

        $generated = false;
        if (empty($password)) {
            $password = 'Aa1'.Str::password(13);
            $generated = true;
        }

        $validation = Validator::make(['password' => $password], [
            'password' => ['required', AdminPasswordPolicy::rule()],
        ]);
        if ($validation->fails()) {
            $this->error('Password must have at least 12 characters, including uppercase, lowercase, and numbers.');

            return 1;
        }

        try {
            DB::transaction(function () use ($name, $username, $email, $password): void {
                // PostgreSQL advisory locks serialize the empty-table case where
                // row locks alone cannot protect a count-then-insert invariant.
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::select('select pg_advisory_xact_lock(?)', [740_210_001]);
                }

                $count = Admin::withTrashed()->count();
                if ($count > 1) {
                    throw new \LogicException('Administrator data is anomalous: more than one account exists.');
                }

                if ($count === 1) {
                    throw new \LogicException('An administrator account already exists.');
                }

                Admin::create([
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'force_password_change' => true,
                ]);
            }, attempts: 3);
        } catch (\LogicException $exception) {
            $this->error($exception->getMessage().' Provisioning is blocked.');

            return 1;
        }

        $this->info('Administrator account provisioned successfully.');
        if ($generated) {
            $this->info("Generated Password: {$password}");
            $this->warn('Please copy this password immediately. It will not be shown again and is not stored in plaintext.');
        }

        return 0;
    }
}
