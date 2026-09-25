<?php

namespace App\Services\Auth;

use App\Models\Hospital\HospitalUser;

/**
 * Permission-sync contract for the mobile/tablet apps.
 *
 * The version is a short hash of everything that decides what the user may
 * do. It is sent on login, on /auth/me and as the X-Permissions-Version
 * header on every authenticated API response, so an app can refresh its
 * cached permissions as soon as an admin changes them on the web panel.
 * Granted keys come from RolePermissionService's cache, which is flushed
 * whenever a role's permissions are saved.
 */
class PermissionsVersion
{
    public function __construct(private readonly RolePermissionService $permissions) {}

    public function for(HospitalUser $user): string
    {
        $user->loadMissing('role');
        $role = $user->role;

        $keys = $this->grantedKeys($user);
        sort($keys);

        return substr(sha1(implode('|', [
            (int) $user->tenant_id,
            (int) $user->id,
            (int) $user->role_id,
            $role?->is_super ? 1 : 0,
            (string) $user->status,
            implode(',', $keys),
        ])), 0, 12);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function modules(HospitalUser $user): array
    {
        $user->loadMissing('role');
        $isSuper = (bool) $user->role?->is_super;
        $effective = $isSuper ? [] : array_flip($this->effectiveKeys($user));

        $out = [];
        foreach (PermissionMatrix::modules() as $moduleKey => $module) {
            if ($isSuper || $this->moduleHasAny($module, $effective)) {
                $out[] = ['key' => (string) $moduleKey, 'label' => (string) ($module['label'] ?? $moduleKey)];
            }
        }

        return $out;
    }

    /**
     * @return array{type: string, hospital_slug: ?string, user_id: int, role_id: ?int, role_slug: ?string, is_super: bool}
     */
    public function scope(HospitalUser $user): array
    {
        $user->loadMissing('role');
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        return [
            'type' => 'hospital',
            'hospital_slug' => $tenant?->slug,
            'user_id' => (int) $user->id,
            'role_id' => $user->role_id ? (int) $user->role_id : null,
            'role_slug' => $user->role?->slug,
            'is_super' => (bool) $user->role?->is_super,
        ];
    }

    /**
     * @return list<string>
     */
    private function grantedKeys(HospitalUser $user): array
    {
        if (! $user->role_id || ! $user->tenant_id) {
            return [];
        }

        return $this->permissions->getGrantedPermissionKeys((int) $user->tenant_id, (int) $user->role_id);
    }

    /**
     * Granted keys resolved to canonical matrix keys, with manage/bundle keys
     * expanded exactly like web RBAC does.
     *
     * @return list<string>
     */
    private function effectiveKeys(HospitalUser $user): array
    {
        $keys = array_map(fn (string $k) => PermissionMatrix::resolve($k), $this->grantedKeys($user));
        $expand = PermissionMatrix::expandMap();

        foreach ($keys as $key) {
            foreach ($expand[$key] ?? [] as $action) {
                $keys[] = $action;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  array<string, mixed>  $module
     * @param  array<string, int>  $effective
     */
    private function moduleHasAny(array $module, array $effective): bool
    {
        foreach ($module['features'] ?? [] as $feature) {
            foreach ($feature['actions'] ?? [] as $action) {
                if (isset($action['key'], $effective[$action['key']])) {
                    return true;
                }
            }
        }

        return false;
    }
}
