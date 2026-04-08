<?php

namespace App\Jobs;

use Database\Seeders\SystemRolesSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CreateTenantRolesAndPermissions — Queued Job
 *
 * Seeds default roles and their permissions for a newly created tenant.
 * Dispatched asynchronously during tenant onboarding to avoid slowing
 * down the registration HTTP response.
 *
 * Queue: default
 * Retries: 3
 * Timeout: 60 seconds
 */
class CreateTenantRolesAndPermissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly int $tenantId
    ) {}

    public function handle(): void
    {
        Log::info("CreateTenantRolesAndPermissions: Starting for tenant #{$this->tenantId}");

        SystemRolesSeeder::seedForTenant($this->tenantId);

        Log::info(
            "CreateTenantRolesAndPermissions: Completed for tenant #{$this->tenantId}."
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(
            "CreateTenantRolesAndPermissions FAILED for tenant #{$this->tenantId}: "
            . $exception->getMessage()
        );
    }
}
