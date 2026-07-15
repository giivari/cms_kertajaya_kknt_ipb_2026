<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateInstallationId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'village:install-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a unique installation ID for this village CMS deployment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = 'VWCM-'.strtoupper(substr(uniqid(), -6)).'-'.mt_rand(100, 999);
        $this->writeNewEnvironmentFileWith($id);
        $this->info("Installation ID generated: {$id}");
    }

    protected function writeNewEnvironmentFileWith($id)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $env = file_get_contents($path);
            if (str_contains($env, 'INSTALLATION_ID=')) {
                $env = preg_replace('/^INSTALLATION_ID=.*$/m', 'INSTALLATION_ID='.$id, $env);
            } else {
                $env .= "\nINSTALLATION_ID={$id}\n";
            }
            file_put_contents($path, $env);
        }
    }
}
