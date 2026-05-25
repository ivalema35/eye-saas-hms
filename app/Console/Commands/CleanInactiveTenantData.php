<?php

namespace App\Console\Commands;

use App\Models\Platform\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CleanInactiveTenantData — Artisan Command
 *
 * CAREFUL: This permanently deletes data for suspended tenants
 * that have been inactive for a configurable grace window.
 *
 * SAFETY: Requires --force flag to actually delete.
 *         Without --force it only shows what would be deleted.
 *
 * Schedule: Weekly (Sundays at 02:00 AM)
 * Usage: php artisan hms:clean-inactive-tenants --days=90 --force
 */
class CleanInactiveTenantData extends Command
{
    protected $signature = 'hms:clean-inactive-tenants
                           {--days=90 : Delete data for tenants inactive for this many days}
                           {--force   : Actually perform deletion (required for safety)}';

    protected $description = 'Soft-delete data for long-inactive tenants (IRREVERSIBLE without --force review).';

    /**
     * Tables that have tenant_id and should be purged.
     * Ordered to avoid FK constraint violations.
     */
    private array $tenantTables = [
        'ot_surgeries', 'ot_lens_details', 'ot_discharge_summaries',
        'ot_payments', 'ot_pre_op', 'ot_dilation_entries', 'ot_bookings',
        'focs', 'secondary_examinations', 'primary_examinations',
        'patients', 'hospital_users',
        'role_permissions', 'roles', 'hospital_settings',
    ];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isForce = $this->option('force');
        $cutoff = now()->subDays($days);

        $this->warn("Looking for tenants inactive for more than {$days} days (before {$cutoff->toDateString()}).");

        // Find eligible tenants
        $tenants = Tenant::where('status', 'inactive')
            ->where('updated_at', '<=', $cutoff)
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No eligible tenants found. Exiting.');

            return self::SUCCESS;
        }

        $this->info("Found {$tenants->count()} tenants eligible for data cleanup:");
        foreach ($tenants as $t) {
            $this->line(" - #{$t->id} {$t->slug} (suspended on: {$t->updated_at?->toDateString()})");
        }

        if (! $isForce) {
            $this->warn('');
            $this->warn('*** Run with --force to actually delete. This is a DRY RUN. ***');

            return self::SUCCESS;
        }

        if (! $this->confirm("Are you SURE you want to permanently delete data for {$tenants->count()} tenants?")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->line("Cleaning tenant #{$tenant->id} ({$tenant->slug})...");

            DB::transaction(function () use ($tenant) {
                foreach ($this->tenantTables as $table) {
                    if (DB::getSchemaBuilder()->hasTable($table)) {
                        $deleted = DB::table($table)->where('tenant_id', $tenant->id)->delete();
                        if ($deleted > 0) {
                            $this->line("  Deleted {$deleted} rows from '{$table}'");
                        }
                    }
                }

                // Mark tenant as deleted in platform
                $tenant->delete(); // SoftDelete
                Log::warning("Tenant #{$tenant->id} ({$tenant->slug}) data purged by CleanInactiveTenantData.");
            });
        }

        $this->info('[hms:clean-inactive-tenants] Cleanup complete.');

        return self::SUCCESS;
    }
}
