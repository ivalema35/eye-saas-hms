<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * PermissionsSeeder — delegates to config/permission_matrix.php via permissions:sync.
 *
 * Fresh install: truncates + syncs + applies role templates for existing tenants.
 * Prefer: php artisan permissions:sync
 */
class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding permissions from permission_matrix...');

        Artisan::call('permissions:sync', [
            '--fresh' => true,
            '--force' => true,
            '--apply-templates' => true,
        ]);

        $this->command?->info(Artisan::output());
    }
}
