<?php

namespace App\Console\Commands;

use App\Jobs\SendSubscriptionEmail;
use App\Models\Platform\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * SendSubscriptionReminders — Artisan Command
 *
 * Sends subscription lifecycle reminder emails to hospital admins.
 * Runs daily at 09:00 AM AFTER CheckSubscriptionExpiry (01:00 AM).
 *
 * Triggers (all 7 per Section 4.3 of requirements):
 *   reminder_7d  — Active tenants expiring in 7 days
 *   reminder_3d  — Active tenants expiring in 3 days
 *   reminder_1d  — Active tenants expiring tomorrow
 *   expired      — Tenants that just moved to grace today (ends_at = yesterday)
 *   grace_day4   — Grace tenants with 3 days of grace remaining (day 4 of 7)
 *   grace_ended  — Tenants that just became inactive (grace_ends_at = yesterday)
 *   inactive_30d — Inactive tenants 30 days after grace ended (data deletion warning)
 *
 * Schedule: Daily at 09:00 AM
 * Usage: php artisan hms:send-subscription-reminders [--dry-run]
 */
class SendSubscriptionReminders extends Command
{
    protected $signature = 'hms:send-subscription-reminders
                           {--dry-run : Show reminders without sending}';

    protected $description = 'Send all 7 subscription lifecycle reminder emails to hospital admins.';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $totalSent = 0;

        $this->info('[hms:send-subscription-reminders] Starting...');

        // ================================================================
        // 1. Pre-expiry reminders — active subscriptions
        // ================================================================
        foreach ([7, 3, 1] as $daysBefore) {
            $targetDate = $today->copy()->addDays($daysBefore);

            $tenants = Tenant::where('status', 'active')
                ->whereHas('subscriptions', fn ($q) => $q->where('status', 'active')->whereDate('ends_at', $targetDate)
                )
                ->get();

            $this->info("D-{$daysBefore}: {$tenants->count()} tenants.");

            foreach ($tenants as $tenant) {
                if (! $isDryRun) {
                    SendSubscriptionEmail::dispatch($tenant, "reminder_{$daysBefore}d");
                    $totalSent++;
                } else {
                    $this->line(" - [DRY] reminder_{$daysBefore}d → {$tenant->admin_email} ({$tenant->slug})");
                }
            }
        }

        // ================================================================
        // 2. Just expired → now in grace (subscription ends_at = yesterday)
        //    CheckSubscriptionExpiry moved these at 01:00 AM this morning.
        // ================================================================
        $justExpired = Tenant::where('status', 'grace')
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'expired')->whereDate('ends_at', $yesterday)
            )
            ->get();

        $this->info("expired (grace started today): {$justExpired->count()} tenants.");

        foreach ($justExpired as $tenant) {
            if (! $isDryRun) {
                SendSubscriptionEmail::dispatch($tenant, 'expired');
                $totalSent++;
            } else {
                $this->line(" - [DRY] expired → {$tenant->admin_email} ({$tenant->slug})");
            }
        }

        // ================================================================
        // 3. Grace day 4 — 3 days of grace remaining (grace_ends_at = today + 3)
        // ================================================================
        $graceDay4Date = $today->copy()->addDays(3);

        $graceDay4 = Tenant::where('status', 'grace')
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'expired')->whereDate('grace_ends_at', $graceDay4Date)
            )
            ->get();

        $this->info("grace_day4 (3 days left): {$graceDay4->count()} tenants.");

        foreach ($graceDay4 as $tenant) {
            if (! $isDryRun) {
                SendSubscriptionEmail::dispatch($tenant, 'grace_day4');
                $totalSent++;
            } else {
                $this->line(" - [DRY] grace_day4 → {$tenant->admin_email} ({$tenant->slug})");
            }
        }

        // ================================================================
        // 4. Grace just ended → now inactive (grace_ends_at = yesterday)
        //    CheckSubscriptionExpiry moved these at 01:00 AM this morning.
        // ================================================================
        $graceEnded = Tenant::where('status', 'inactive')
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'expired')->whereDate('grace_ends_at', $yesterday)
            )
            ->get();

        $this->info("grace_ended (inactive today): {$graceEnded->count()} tenants.");

        foreach ($graceEnded as $tenant) {
            if (! $isDryRun) {
                SendSubscriptionEmail::dispatch($tenant, 'grace_ended');
                $totalSent++;
            } else {
                $this->line(" - [DRY] grace_ended → {$tenant->admin_email} ({$tenant->slug})");
            }
        }

        // ================================================================
        // 5. 30 days after going inactive — data deletion warning
        //    grace_ends_at = 30 days ago means inactive for ~30 days.
        // ================================================================
        $inactive30Date = $today->copy()->subDays(30);

        $inactive30 = Tenant::where('status', 'inactive')
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'expired')->whereDate('grace_ends_at', $inactive30Date)
            )
            ->get();

        $this->info("inactive_30d (data deletion warning): {$inactive30->count()} tenants.");

        foreach ($inactive30 as $tenant) {
            if (! $isDryRun) {
                SendSubscriptionEmail::dispatch($tenant, 'inactive_30d');
                $totalSent++;
            } else {
                $this->line(" - [DRY] inactive_30d → {$tenant->admin_email} ({$tenant->slug})");
            }
        }

        // ================================================================

        if (! $isDryRun) {
            $this->info("Total reminders dispatched: {$totalSent}");
        } else {
            $this->warn('[DRY RUN] No emails were sent.');
        }

        $this->info('[hms:send-subscription-reminders] Done.');

        return self::SUCCESS;
    }
}
