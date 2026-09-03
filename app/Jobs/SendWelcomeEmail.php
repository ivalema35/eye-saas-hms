<?php

/**
 * SendWelcomeEmail.php
 *
 * PURPOSE: Hospital admin ko welcome email with login credentials.
 *          Sent when SuperAdmin approves registration or creates hospital directly.
 *
 * DISPATCHED BY: TenantService::approveRegistration(), TenantService::createTenant()
 */

namespace App\Jobs;

use App\Models\Hospital\HospitalUser;
use App\Models\Platform\Tenant;
use App\Models\Role\Role;
use App\Services\Platform\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly Tenant $tenant
    ) {}

    public function handle(): void
    {
        $tenant = $this->tenant->fresh();

        if (! $tenant) {
            Log::warning('SendWelcomeEmail: Tenant no longer exists');

            return;
        }

        $email = $tenant->admin_email;

        if (! $email) {
            Log::warning("SendWelcomeEmail: No email for tenant #{$tenant->id}");

            return;
        }

        $loginPassword = $this->resolveAdminPassword($tenant);

        Log::info("SendWelcomeEmail → {$email} (Tenant: {$tenant->slug})");

        MailConfigService::apply();

        try {
            Mail::send('emails.welcome', [
                'tenant' => $tenant,
                'loginPassword' => $loginPassword,
            ], function ($message) use ($email, $tenant) {
                $message->to($email)
                    ->subject("Your Hospital Has Been Approved — {$tenant->name} | EYENOSIS");
            });
        } catch (\Throwable $exception) {
            Log::error(
                "SendWelcomeEmail mail send failed for tenant #{$tenant->id}: "
                .$exception->getMessage()
            );
        }
    }

    private function resolveAdminPassword(Tenant $tenant): ?string
    {
        $adminRoleId = Role::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'hospital_admin')
            ->value('id');

        if (! $adminRoleId) {
            return null;
        }

        return HospitalUser::withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('role_id', $adminRoleId)
            ->value('original_password');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(
            "SendWelcomeEmail FAILED for tenant #{$this->tenant->id}: "
            .$exception->getMessage()
        );
    }
}
