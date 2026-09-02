<?php

namespace App\Jobs;

use App\Models\Platform\Tenant;
use App\Services\Platform\MailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendHospitalPasswordChangedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $newPassword
    ) {}

    public function handle(): void
    {
        $tenant = $this->tenant->fresh();

        if (! $tenant) {
            Log::warning('SendHospitalPasswordChangedEmail: Tenant no longer exists');

            return;
        }

        $email = $tenant->admin_email;

        if (! $email) {
            Log::warning("SendHospitalPasswordChangedEmail: No email for tenant #{$tenant->id}");

            return;
        }

        Log::info("SendHospitalPasswordChangedEmail → {$email} (Tenant: {$tenant->slug})");

        MailConfigService::apply();

        try {
            Mail::send('emails.hospital-password-changed', [
                'tenant' => $tenant,
                'newPassword' => $this->newPassword,
            ], function ($message) use ($email, $tenant) {
                $message->to($email)
                    ->subject("Your Password Has Been Changed — {$tenant->name} | EYENOSIS");
            });
        } catch (\Throwable $exception) {
            Log::error(
                "SendHospitalPasswordChangedEmail failed for tenant #{$tenant->id}: "
                .$exception->getMessage()
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(
            "SendHospitalPasswordChangedEmail FAILED for tenant #{$this->tenant->id}: "
            .$exception->getMessage()
        );
    }
}
