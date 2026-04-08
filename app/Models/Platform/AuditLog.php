<?php

/**
 * AuditLog.php
 *
 * PURPOSE: Super Admin ke platform actions ka audit trail.
 *          Kya kiya, kisne kiya, kab kiya — sab record hoga.
 *
 * TENANT-SCOPED: NO (Platform-level model)
 * TABLE: audit_logs
 */

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    // Audit logs kabhi update nahi hote — sirf create
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'tenant_id',
        'action',
        'description',
        'ip_address',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(PlatformAdmin::class, 'admin_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
