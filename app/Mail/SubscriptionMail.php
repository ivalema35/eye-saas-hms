<?php

namespace App\Mail;

use App\Models\Platform\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * SubscriptionMail — Mailable
 *
 * Sends subscription lifecycle emails to hospital admins.
 * Used by the SendSubscriptionEmail queued job.
 *
 * EMAIL TYPES:
 *   trial_welcome   — New hospital trial started
 *   reminder_7d     — Expiry reminder 7 days before
 *   reminder_3d     — Expiry reminder 3 days before
 *   reminder_1d     — Final warning 1 day before
 *   expired         — Subscription expired, 7-day grace started
 *   grace_day4      — Grace period ending in 3 days (day 4 of 7)
 *   grace_ended     — Account deactivated, data safe for 30 days
 *   inactive_30d    — Data deletion warning (30 days since inactive)
 *   renewal_success — Subscription renewed successfully
 *   suspended       — Account manually suspended by super admin
 *
 * VIEW: resources/views/emails/subscription.blade.php
 */
class SubscriptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $emailType
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->getSubject());
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription');
    }

    private function getSubject(): string
    {
        return match ($this->emailType) {
            'trial_welcome' => 'Welcome to Eye HMS — Your 14-Day Free Trial Has Started',
            'reminder_7d' => 'Reminder: Your Subscription Expires in 7 Days — Eye HMS',
            'reminder_3d' => 'ACTION REQUIRED: Subscription Expires in 3 Days — Eye HMS',
            'reminder_1d' => 'FINAL WARNING: Subscription Expires Tomorrow — Eye HMS',
            'expired' => 'Subscription Expired — 7-Day Grace Period Has Started — Eye HMS',
            'grace_day4' => 'Grace Period Ending in 3 Days — Renew Now — Eye HMS',
            'grace_ended' => 'Account Deactivated — Your Data is Safe for 30 Days — Eye HMS',
            'inactive_30d' => 'Data Deletion Warning — Renew to Keep Your Hospital Data — Eye HMS',
            'renewal_success' => 'Subscription Renewed Successfully — Eye HMS',
            'suspended' => 'Your Account Has Been Suspended — Contact Support — Eye HMS',
            default => 'Eye HMS Platform Notification',
        };
    }
}
