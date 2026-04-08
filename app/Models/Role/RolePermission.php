<?php

/**
 * RolePermission.php
 *
 * PURPOSE: Role aur Permission ka pivot model.
 *          is_granted boolean — checkbox-based manual assignment.
 *
 * DESIGN:
 *   is_granted = true  → Hospital Admin ne ye permission is role ko di hai ✓
 *   is_granted = false → Permission nahi di / unchecked
 *
 * TENANT-SCOPED: INDIRECT (role_id → roles → tenant_id)
 * TABLE: role_permissions
 */

namespace App\Models\Role;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
        'is_granted',
        'updated_by',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
