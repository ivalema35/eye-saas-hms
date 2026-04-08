<?php

use App\Console\Commands\CheckSubscriptionExpiry;
use App\Console\Commands\CleanInactiveTenantData;
use App\Console\Commands\SendSubscriptionReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================
// HMS SaaS — Scheduled Tasks
// ============================================================

// Check subscription expiry: Daily at 01:00 AM
Schedule::command(CheckSubscriptionExpiry::class)
        ->dailyAt('01:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->onFailure(fn () => \Illuminate\Support\Facades\Log::error('hms:check-subscription-expiry scheduled job failed.'));

// Send renewal reminders: Daily at 09:00 AM
Schedule::command(SendSubscriptionReminders::class)
        ->dailyAt('09:00')
        ->withoutOverlapping()
        ->runInBackground();

// Clean inactive tenant data: Every Sunday at 02:00 AM
// SAFETY NOTE: --force is NOT passed here — runs as dry-run in production cron
// To actually delete, run manually: php artisan hms:clean-inactive-tenants --days=90 --force
Schedule::command(CleanInactiveTenantData::class, ['--days=90'])
        ->weekly()
        ->sundays()
        ->at('02:00')
        ->withoutOverlapping()
        ->runInBackground();
