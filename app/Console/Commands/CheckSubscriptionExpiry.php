<?php

namespace App\Console\Commands;

use App\Models\Platform\Subscription;
use App\Models\Platform\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * CheckSubscriptionExpiry — Artisan Command
 *
 * Runs daily via scheduler to:
 *   1. Mark subscriptions as 'expired' when ends_at <= today
 *   2. Move active tenants to 'grace' (if subscription has grace_ends_at) or 'inactive'
 *   3. Move 'grace' tenants to 'inactive' when subscription's grace_ends_at passes
 *
 * Schedule: Daily at 01:00 AM
 * Usage: php artisan hms:check-subscription-expiry
 */
class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'hms:check-subscription-expiry
                           {--dry-run : Show what would change without saving}';

    protected $description = 'Check subscription expiry dates and update statuses accordingly.';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('[hms:check-subscription-expiry] Starting...');
        $today = Carbon::today();

        // ============================================================
        // Step 1: Expire active subscriptions past ends_at
        // ============================================================
        $expiredQuery = Subscription::where('status', 'active')
            ->where('ends_at', '<', $today);

        $this->info("Found {$expiredQuery->count()} subscriptions to expire.");

        if (! $isDryRun) {
            $expiredQuery->update(['status' => 'expired']);

            // Active tenants with an expired subscription that has a grace period → grace
            Tenant::where('status', 'active')
                ->whereHas('subscriptions', fn ($q) => $q->where('status', 'expired')->whereNotNull('grace_ends_at')
                )
                ->update(['status' => 'grace']);

            // Active tenants with an expired subscription and no grace period → inactive immediately
            Tenant::where('status', 'active')
                ->whereHas('subscriptions', fn ($q) => $q->where('status', 'expired')->whereNull('grace_ends_at')
                )
                ->update(['status' => 'expired']);
        }

        // ============================================================
        // Step 2: Move grace tenants to inactive when grace period ends
        // ============================================================
        $graceExpiredQuery = Tenant::where('status', 'grace')
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'expired')
                ->whereNotNull('grace_ends_at')
                ->where('grace_ends_at', '<', $today)
            );

        $this->info("Found {$graceExpiredQuery->count()} tenants with expired grace period.");

        if (! $isDryRun) {
            $inactivatedCount = 0;
            $graceExpiredQuery->each(function (Tenant $tenant) use (&$inactivatedCount) {
                $tenant->update(['status' => 'expired']);
                $inactivatedCount++;
                Log::warning("Tenant #{$tenant->id} ({$tenant->slug}) set expired: grace period ended.");
            });
            $this->info("Set {$inactivatedCount} tenants to inactive.");
        }

        // Step 3: Trial / grace / active tenants whose access date has passed → expired
        $accessQuery = Tenant::whereIn('status', ['trial', 'active', 'grace'])
            ->with('subscriptions');

        $toExpire = $accessQuery->get()->filter(fn (Tenant $t) => $t->isPlanExpired());
        $this->info("Found {$toExpire->count()} tenants to mark expired (plan date passed).");

        if (! $isDryRun) {
            foreach ($toExpire as $tenant) {
                $tenant->update(['status' => 'expired']);
                Log::warning("Tenant #{$tenant->id} ({$tenant->slug}) set expired: plan date passed.");
            }
        }

        if ($isDryRun) {
            $this->warn('[DRY RUN] No changes were saved.');
        }

        $this->info('[hms:check-subscription-expiry] Done.');

        return self::SUCCESS;
    }
}
