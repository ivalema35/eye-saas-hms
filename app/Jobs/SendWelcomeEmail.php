<?php

/**
 * SendWelcomeEmail.php
 *
 * PURPOSE: Naye tenant ko welcome email bhejo with login credentials.
 *          Phase 2 me log driver par bhejega (actual SMTP Phase 3 me).
 *
 * DISPATCHED BY: TenantService::createTenant()
 */

namespace App\Jobs;

use App\Models\Platform\Tenant;
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
        $email = $this->tenant->admin_email;

        if (! $email) {
            Log::warning("SendWelcomeEmail: No email for tenant #{$this->tenant->id}");

            return;
        }

        Log::info("SendWelcomeEmail → {$email} (Tenant: {$this->tenant->slug})");

        try {
            Mail::send('emails.welcome', [
                'tenant' => $this->tenant,
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Welcome to Eye HMS — Your Hospital is Ready!');
            });
        } catch (\Throwable $exception) {
            Log::error(
                "SendWelcomeEmail mail send failed for tenant #{$this->tenant->id}: "
                .$exception->getMessage()
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(
            "SendWelcomeEmail FAILED for tenant #{$this->tenant->id}: "
            .$exception->getMessage()
        );
    }
}
