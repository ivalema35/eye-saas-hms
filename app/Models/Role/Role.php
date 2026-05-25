<?php

/**
 * Role.php
 *
 * PURPOSE: Hospital role model — tenant-scoped.
 *
 * KEY FIELDS:
 *   is_system = true  → Role ko delete nahi kar sakte (but permissions edit kar sakte hain)
 *   is_super  = true  → Is role ke users ka permission check bypass hoga
 *                       (Sirf Hospital Admin role ke liye — ek hi role is_super hoga)
 *
 * PERMISSION ASSIGNMENT:
 *   - Hospital Admin MANUALLY permissions set karta hai
 *   - Koi bhi auto-seeding of permissions NAHI hoti
 *   - role_permissions table mein is_granted boolean per permission
 *
 * TENANT-SCOPED: YES (BelongsToTenant trait)
 * TABLE: roles
 */

namespace App\Models\Role;

use App\Models\Hospital\HospitalUser;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'is_system',
        'is_super',
        'description',
        'color',
        'created_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_super' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /**
     * All permissions granted to this role.
     * Only returns permissions where is_granted = true.
     */
    public function grantedPermissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )
            ->wherePivot('is_granted', true)
            ->withPivot('is_granted', 'updated_by')
            ->withTimestamps();
    }

    /**
     * All role_permissions rows (including is_granted = false).
     * Useful for the permission edit UI — show all permissions with current state.
     */
    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * Users assigned to this role.
     */
    public function users()
    {
        return $this->hasMany(HospitalUser::class);
    }

    // ── Helper Methods ────────────────────────────────────────────────────

    /**
     * Can this role be deleted?
     * System roles cannot be deleted — but custom roles can (if no users assigned).
     */
    public function isDeletable(): bool
    {
        return ! $this->is_system && $this->users()->count() === 0;
    }

    /**
     * Get granted permission action keys as array.
     * Used for display in UI.
     */
    public function getGrantedPermissionKeys(): array
    {
        return $this->grantedPermissions()->pluck('action')->toArray();
    }
}
