<?php

/**
 * Permission.php
 *
 * PURPOSE: Platform-level master permission list.
 *          Ye global list hai — tenant-specific nahi.
 *          action format: 'module.submodule.action' (e.g., 'opd.patient.register')
 *
 * TENANT-SCOPED: NO (Platform-level model — NO BelongsToTenant)
 * TABLE: permissions
 */

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'module',
        'action',
        'label',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        )->withPivot('is_granted')->withTimestamps();
    }
}
