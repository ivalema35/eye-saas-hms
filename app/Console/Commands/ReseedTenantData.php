<?php

namespace App\Console\Commands;

use App\Jobs\SeedTenantDefaultMasters;
use App\Models\Platform\Tenant;
use Database\Seeders\SystemRolesSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ReseedTenantData
 *
 * Existing hospitals ke liye:
 *   1. OT role permissions fix karo (SystemRolesSeeder re-run)
 *   2. Empty master tables seed karo (SeedTenantDefaultMasters dispatch)
 *
 * Usage:
 *   php artisan hms:reseed-tenant           — all tenants
 *   php artisan hms:reseed-tenant --id=3    — specific tenant
 *   php artisan hms:reseed-tenant --masters-only
 *   php artisan hms:reseed-tenant --perms-only
 */
class ReseedTenantData extends Command
{
    protected $signature = 'hms:reseed-tenant
                            {--id= : Specific tenant ID (default: all)}
                            {--masters-only : Only re-seed master data, skip permissions}
                            {--perms-only : Only fix role permissions, skip master data}';

    protected $description = 'Fix OT role permissions + re-seed master data for existing hospitals';

    public function handle(): int
    {
        $tenantId    = $this->option('id');
        $mastersOnly = $this->option('masters-only');
        $permsOnly   = $this->option('perms-only');

        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', $tenantId);
        }
        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return 1;
        }

        $this->info("Processing {$tenants->count()} tenant(s)...\n");

        $permErrors    = 0;
        $masterErrors  = 0;

        foreach ($tenants as $tenant) {
            $this->line("── Tenant #{$tenant->id}: <info>{$tenant->name}</info> ({$tenant->slug})");

            // ── 1. Role permissions ──────────────────────────────────
            if (! $mastersOnly) {
                try {
                    SystemRolesSeeder::seedForTenant($tenant->id);
                    $this->line("   ✅ Role permissions updated");
                } catch (\Throwable $e) {
                    $this->error("   ❌ Permissions error: {$e->getMessage()}");
                    $permErrors++;
                }
            }

            // ── 2. Master data ───────────────────────────────────────
            if (! $permsOnly) {
                $emptyMasters = $this->countEmptyMasters($tenant->id);

                if ($emptyMasters > 0) {
                    SeedTenantDefaultMasters::dispatch($tenant->id);
                    $this->line("   ✅ Master data job dispatched ({$emptyMasters} empty table(s))");
                } else {
                    $this->line("   ℹ  Master data already seeded — skipping");
                }
            }
        }

        $this->newLine();
        $totalErrors = $permErrors + $masterErrors;
        if ($totalErrors === 0) {
            $this->info('Done. All tenants processed successfully.');
        } else {
            $this->warn("Done with {$totalErrors} error(s). Check logs for details.");
        }

        return $totalErrors > 0 ? 1 : 0;
    }

    private function countEmptyMasters(int $tenantId): int
    {
        $tables = [
            'tbl_cases', 'chief_complaints', 'kcos',
            'tbl_master_vn', 'tbl_master_sac', 'tbl_master_advice',
            'tbl_master_diagnosis', 'tbl_master_sph_cyl',
        ];

        $empty = 0;
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->where('tenant_id', $tenantId)->count();
                if ($count === 0) {
                    $empty++;
                }
            } catch (\Throwable) {
                // Table may not exist in older installs
            }
        }

        return $empty;
    }
}
