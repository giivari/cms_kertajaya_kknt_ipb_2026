<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        if (Admin::count() > 0) {
            $this->error('An administrator account already exists. Provisioning is disabled.');

            return 1;
        }

        $name = $this->option('name') ?? $this->ask('Administrator Name', 'Village Administrator');
        $username = $this->option('username') ?? $this->ask('Username', 'admin');
        $email = $this->option('email') ?? $this->ask('Email Address', 'admin@example.com');

        $password = $this->secret('Password (leave blank to auto-generate)');

        $generated = false;
        if (empty($password)) {
            $password = Str::password(16);
            $generated = true;
        }

        Admin::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'force_password_change' => true,
        ]);

        $this->info('Administrator account provisioned successfully.');
        if ($generated) {
            $this->info("Generated Password: {$password}");
            $this->warn('Please copy this password immediately. It will not be shown again and is not stored in plaintext.');
        }

        return 0;
    }
}
