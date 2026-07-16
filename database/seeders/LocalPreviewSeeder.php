<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LocalPreviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->error('Local preview seeder can only be run in local environment.');

            return;
        }

        $this->command->info('Local preview seeded successfully.');
    }
}
