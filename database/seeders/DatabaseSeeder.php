<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters:
     *   1. PermissionsSeeder  — Master permissions (no FK dependencies)
     *   2. PlatformAdminSeeder — SuperAdmin account
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            PlatformAdminSeeder::class,
            MasterSphCylSeeder::class,
            DosageSeeder::class,
            MedicineTypeSeeder::class,
            MedicineCategorySeeder::class,
            MedicineRouteSeeder::class,
        ]);
    }
}
