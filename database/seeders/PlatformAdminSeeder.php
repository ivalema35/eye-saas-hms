<?php

namespace Database\Seeders;

use App\Models\Platform\PlatformAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * PlatformAdminSeeder
 *
 * Creates the initial SuperAdmin account.
 *
 * IMPORTANT: Change the password immediately after first login!
 * For production, set SUPERADMIN_EMAIL + SUPERADMIN_PASSWORD in .env
 * before running this seeder.
 */
class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('SUPERADMIN_EMAIL', 'superadmin@eyehms.com');
        $password = env('SUPERADMIN_PASSWORD', 'SuperAdmin@123!');
        $name     = env('SUPERADMIN_NAME', 'Super Admin');

        $admin = PlatformAdmin::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($password),
                'role'     => 'super_admin',
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info("SuperAdmin created: {$email}");
            $this->command->warn("IMPORTANT: Change the default password immediately!");
        } else {
            $this->command->info("SuperAdmin already exists: {$email}");
        }
    }
}
