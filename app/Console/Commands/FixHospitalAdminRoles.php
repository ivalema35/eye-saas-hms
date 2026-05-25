<?php

namespace App\Console\Commands;

use App\Models\Hospital\HospitalUser;
use App\Models\Role\Role;
use Database\Seeders\SystemRolesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * FixHospitalAdminRoles
 *
 * Existing hospitals jinka admin ka role_id null hai, unhe fix karta hai.
 * Ek baar run karo, phir delete ya archive kar sakte ho.
 *
 * Run: php artisan hms:fix-admin-roles
 */
class FixHospitalAdminRoles extends Command
{
    protected $signature = 'hms:fix-admin-roles {--dry-run : Sirf dikhao, save mat karo}';

    protected $description = 'Existing hospital admins ka role_id null se fix karo';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Checking hospital users with null role_id...');

        // Sab null role_id users
        $usersWithNullRole = HospitalUser::withoutTenantScope()
            ->whereNull('role_id')
            ->with('tenant')
            ->get();

        if ($usersWithNullRole->isEmpty()) {
            $this->info('✅ No users with null role_id found. All good!');

            return 0;
        }

        $this->warn("Found {$usersWithNullRole->count()} user(s) with null role_id:");

        $fixed = 0;
        $errors = 0;

        foreach ($usersWithNullRole as $user) {
            $tenantId = $user->tenant_id;

            // Hospital Admin role dhundo
            $adminRole = Role::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('slug', 'hospital_admin')
                ->first();

            if (! $adminRole) {
                // Roles nahi hain — seed karo pehle
                $this->warn("  Tenant #{$tenantId}: No hospital_admin role found. Seeding roles...");

                if (! $dryRun) {
                    // Config set karo taaki BelongsToTenant work kare
                    Config::set('app.tenant_id', $tenantId);

                    try {
                        SystemRolesSeeder::seedForTenant($tenantId);

                        $adminRole = Role::withoutTenantScope()
                            ->where('tenant_id', $tenantId)
                            ->where('slug', 'hospital_admin')
                            ->first();
                    } catch (\Exception $e) {
                        $this->error("  ERROR seeding tenant #{$tenantId}: ".$e->getMessage());
                        $errors++;

                        continue;
                    }
                }
            }

            if ($adminRole) {
                $this->line("  User #{$user->id} ({$user->email}) → Tenant #{$tenantId} → Role: hospital_admin (#{$adminRole->id})");

                if (! $dryRun) {
                    HospitalUser::withoutTenantScope()
                        ->where('id', $user->id)
                        ->update(['role_id' => $adminRole->id]);
                    $fixed++;
                } else {
                    $this->comment("  [DRY RUN] Would update role_id to #{$adminRole->id}");
                }
            } else {
                $this->error("  User #{$user->id}: Could not find/create hospital_admin role for tenant #{$tenantId}");
                $errors++;
            }
        }

        Config::set('app.tenant_id', 0); // Reset

        if (! $dryRun) {
            $this->info("✅ Fixed: {$fixed} user(s)");
        }

        if ($errors > 0) {
            $this->error("❌ Errors: {$errors}");
        }

        return $errors > 0 ? 1 : 0;
    }
}
