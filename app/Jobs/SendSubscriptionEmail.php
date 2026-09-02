<?php

namespace App\Jobs;

use App\Mail\SubscriptionMail;
use App\Models\Platform\PlatformNotification;
use App\Models\Platform\Tenant;
use App\Services\Platform\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SendSubscriptionEmail — Queued Job
 *
 * Sends subscription lifecycle emails to hospital admins.
 *
 * Supported email types:
 *   - renewal_success  : Subscription renewed successfully
 *   - reminder_7d      : Expiry reminder 7 days before
 *   - reminder_3d      : Expiry reminder 3 days before
 *   - suspended        : Account suspended notification
 *   - trial_welcome    : New trial account welcome email
 *
 * Queue: notifications
 * Retries: 3
 * Timeout: 30 seconds
 */
class SendSubscriptionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $emailType
    ) {}

    public function handle(): void
    {
        $email = $this->tenant->admin_email;

        if (! $email) {
            Log::warning("SendSubscriptionEmail: No email for tenant #{$this->tenant->id}");

            return;
        }

        try {
            MailConfigService::apply();

            Mail::to($email)->send(new SubscriptionMail($this->tenant, $this->emailType));

            PlatformNotification::create([
                'tenant_id' => $this->tenant->id,
                'type' => $this->emailType,
                'subject' => (new SubscriptionMail($this->tenant, $this->emailType))->envelope()->subject,
                'recipient_email' => $email,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning(
                "SendSubscriptionEmail FAILED [{$this->emailType}] tenant #{$this->tenant->id}: "
                .$exception->getMessage()
            );

            PlatformNotification::create([
                'tenant_id' => $this->tenant->id,
                'type' => $this->emailType,
                'subject' => (new SubscriptionMail($this->tenant, $this->emailType))->envelope()->subject,
                'recipient_email' => $email,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(
            "SendSubscriptionEmail FAILED [{$this->emailType}] tenant #{$this->tenant->id}: "
            .$exception->getMessage()
        );

        PlatformNotification::create([
            'tenant_id' => $this->tenant->id,
            'type' => $this->emailType,
            'subject' => (new SubscriptionMail($this->tenant, $this->emailType))->envelope()->subject,
            'recipient_email' => $this->tenant->admin_email ?? '',
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
