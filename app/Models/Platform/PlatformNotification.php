<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

/**
 * PlatformNotification — Platform Model
 *
 * Records all platform emails sent to hospital admins.
 * Used for the notification center history and audit trail.
 *
 * TABLE: platform_notifications
 */
class PlatformNotification extends Model
{
    protected $table = 'platform_notifications';

    protected $fillable = [
        'tenant_id',
        'type',
        'subject',
        'recipient_email',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
