<?php

/**
 * RolePermissionService.php
 *
 * PURPOSE: Hospital-level RBAC service.
 *          Manual permission check — checkbox-based, no auto-defaults.
 *
 * FLOW:
 *   1. Current user load karo (hospital_user guard)
 *   2. User ka role load karo
 *   3. is_super = true → bypass (Hospital Admin)
 *   4. role_permissions from DB/cache load karo
 *   5. Permission key ka is_granted check karo
 *
 * CACHE:
 *   Key:  hms_perms_{tenant_id}_{role_id}
 *   TTL:  60 seconds
 *   Type: Array of permission action keys (only granted ones)
 *
 * NO AUTO-SEEDING:
 *   Ye service koi bhi default permissions assign nahi karta.
 *   Hospital Admin manually UI se permissions set karta hai.
 */

namespace App\Services\Auth;

use App\Models\Role\Permission;
use App\Models\Role\Role;
use App\Models\Role\RolePermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RolePermissionService
{
    /**
     * Check if the current logged-in hospital user has the given permission.
     *
     * Accepts matrix keys (patient_view) or legacy dotted keys (opd.patient.view).
     */
    public function can(string $permissionKey): bool
    {
        $user = Auth::guard('hospital_user')->user();

        if ($user === null) {
            return false;
        }

        $user->loadMissing('role');

        // is_super role = Hospital Admin — bypass all checks
        if ($user->role?->is_super) {
            return true;
        }

        $roleId = $user->role_id;
        $tenantId = $user->tenant_id;

        if (! $roleId || ! $tenantId) {
            return false;
        }

        $grantedKeys = $this->getGrantedPermissionKeys($tenantId, $roleId);

        foreach (PermissionMatrix::aliasesFor($permissionKey) as $alias) {
            if (in_array($alias, $grantedKeys, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current logged-in hospital user has any of the given permissions.
     *
     * @param  array<string>  $permissionKeys
     */
    public function canAny(array $permissionKeys): bool
    {
        foreach ($permissionKeys as $permissionKey) {
            if ($this->can($permissionKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all GRANTED permission keys for a role (cached).
     *
     * Returns: ['opd.patient.view', 'opd.patient.register', 'opd.exam.primary', ...]
     * Only returns keys where is_granted = true.
     *
     * @return array<string>
     */
    public function getGrantedPermissionKeys(int $tenantId, int $roleId): array
    {
        $cacheKey = "hms_perms_{$tenantId}_{$roleId}";

        return Cache::remember($cacheKey, 60, function () use ($roleId) {
            return RolePermission::where('role_id', $roleId)
                ->where('is_granted', true)
                ->with('permission:id,action')
                ->get()
                ->pluck('permission.action')
                ->filter()
                ->values()
                ->toArray();
        });
    }

    /**
     * Permissions for Role UI, ordered by permission_matrix (module → feature → actions).
     *
     * Returns:
     * [
     *   'opd' => [
     *     'label' => 'OPD',
     *     'features' => [
     *       'patient' => [
     *         'label' => 'Patients',
     *         'permissions' => [
     *           ['id' => 1, 'action' => 'patient_view', 'label' => '...', 'is_granted' => true],
     *         ],
     *       ],
     *     ],
     *   ],
     * ]
     */
    public function getPermissionsForRoleUI(int $roleId): array
    {
        $byAction = Permission::query()->get()->keyBy('action');

        $grantedIds = $roleId > 0
            ? RolePermission::where('role_id', $roleId)
                ->where('is_granted', true)
                ->pluck('permission_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $grouped = [];

        foreach (PermissionMatrix::modules() as $moduleKey => $module) {
            $featuresOut = [];
            $features = $module['features'] ?? [];
            uasort($features, fn ($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

            foreach ($features as $featureKey => $feature) {
                $actions = $feature['actions'] ?? [];
                usort($actions, fn ($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

                $perms = [];
                foreach ($actions as $action) {
                    $key = (string) ($action['key'] ?? '');
                    $perm = $byAction->get($key);
                    if (! $perm) {
                        continue;
                    }

                    $perms[] = [
                        'id' => $perm->id,
                        'action' => $perm->action,
                        'label' => $action['label'] ?? $perm->label,
                        'description' => $perm->description,
                        'is_granted' => in_array((int) $perm->id, $grantedIds, true),
                    ];
                }

                if ($perms === []) {
                    continue;
                }

                $featuresOut[$featureKey] = [
                    'label' => $feature['label'] ?? $featureKey,
                    'permissions' => $perms,
                ];
            }

            if ($featuresOut === []) {
                continue;
            }

            $grouped[$moduleKey] = [
                'label' => $module['label'] ?? $moduleKey,
                'features' => $featuresOut,
            ];
        }

        return $grouped;
    }

    /**
     * Save permissions for a role from the UI form submission.
     *
     * @param  array  $grantedPermissionIds  Array of permission IDs that were checked
     * @param  int  $updatedBy  hospital_users.id who made the change
     */
    public function saveRolePermissions(int $roleId, array $grantedPermissionIds, int $updatedBy): void
    {
        $allPermissions = Permission::pluck('id')->toArray();

        // Cast to int — form submissions send string values; in_array strict comparison would fail
        $grantedPermissionIds = array_map('intval', $grantedPermissionIds);

        $upsertData = [];
        $now = now();

        foreach ($allPermissions as $permId) {
            $upsertData[] = [
                'role_id' => $roleId,
                'permission_id' => $permId,
                'is_granted' => in_array($permId, $grantedPermissionIds, true),
                'updated_by' => $updatedBy,
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        // Bulk upsert — all permissions in one query
        RolePermission::upsert(
            $upsertData,
            ['role_id', 'permission_id'],   // unique keys
            ['is_granted', 'updated_by', 'updated_at']  // columns to update
        );

        // Flush cache for this role
        $tenantId = Role::find($roleId)?->tenant_id;
        if ($tenantId) {
            $this->flushCache($tenantId, $roleId);
        }

        Log::info("Permissions updated for role #{$roleId} by user #{$updatedBy}");
    }

    /**
     * Flush the permissions cache for a specific role.
     * Call this after updating role permissions.
     */
    public function flushCache(int $tenantId, int $roleId): void
    {
        Cache::forget("hms_perms_{$tenantId}_{$roleId}");
    }

    /**
     * Flush ALL permission caches for a tenant.
     * Use after bulk permission updates or role changes.
     */
    public function flushTenantCache(int $tenantId): void
    {
        Role::where('tenant_id', $tenantId)
            ->pluck('id')
            ->each(fn ($roleId) => Cache::forget("hms_perms_{$tenantId}_{$roleId}"));
    }
}
