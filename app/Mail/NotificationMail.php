<?php

namespace App\Mail;

use App\Models\Platform\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * NotificationMail — Mailable
 *
 * Sends custom broadcast notifications from the Super Admin
 * notification center to hospital admins.
 *
 * VIEW: resources/views/emails/notification.blade.php
 */
class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $notificationSubject,
        public readonly string $notificationBody
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notificationSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notification');
    }
}
