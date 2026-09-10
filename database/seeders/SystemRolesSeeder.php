<?php

namespace Database\Seeders;

use App\Models\Role\Permission;
use App\Models\Role\Role;
use App\Models\Role\RolePermission;
use App\Services\Auth\PermissionMatrix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SystemRolesSeeder extends Seeder
{
    private array $systemRoles = [
        ['name' => 'Hospital Admin', 'slug' => 'hospital_admin', 'color' => '#1B4F72', 'is_super' => true, 'is_system' => true],
        ['name' => 'Doctor', 'slug' => 'doctor', 'color' => '#2980B9', 'is_super' => false, 'is_system' => false],
        ['name' => 'Receptionist', 'slug' => 'receptionist', 'color' => '#27AE60', 'is_super' => false, 'is_system' => false],
        ['name' => 'Accountant', 'slug' => 'accountant', 'color' => '#117A65', 'is_super' => false, 'is_system' => true],
        ['name' => 'Ward Management', 'slug' => 'ward_management', 'color' => '#7D3C98', 'is_super' => false, 'is_system' => true],
        ['name' => 'OT Assistant', 'slug' => 'ot_assistant', 'color' => '#CA6F1E', 'is_super' => false, 'is_system' => true],
        ['name' => 'Discharge Counter', 'slug' => 'discharge_counter', 'color' => '#2E86C1', 'is_super' => false, 'is_system' => true],
    ];

    public static function seedForTenant(int $tenantId, ?int $adminId = null): void
    {
        (new self)->runForTenant($tenantId, $adminId);
    }

    public function run(): void
    {
        $this->command->warn('Direct run not supported. Use SystemRolesSeeder::seedForTenant($tenantId).');
    }

    private function runForTenant(int $tenantId, ?int $adminId): void
    {
        $allPermissions = Permission::all()->keyBy('action');

        if ($allPermissions->isEmpty()) {
            Log::error('SystemRolesSeeder: permissions table is empty! Run PermissionsSeeder first.');

            return;
        }

        foreach ($this->systemRoles as $roleData) {
            $role = Role::withoutTenantScope()->firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'color' => $roleData['color'],
                    'is_system' => $roleData['is_system'],
                    'is_super' => $roleData['is_super'],
                    'created_by' => $adminId,
                ]
            );

            $this->assignDefaultPermissions($role, $allPermissions);
        }
    }

    private function assignDefaultPermissions(Role $role, $allPermissions): void
    {
        if ($role->is_super) {
            $this->applyPermissions(
                $role,
                $allPermissions,
                array_fill_keys($allPermissions->keys()->toArray(), true)
            );

            return;
        }

        $templates = PermissionMatrix::roleTemplates();
        $grantedRaw = $templates[$role->slug] ?? [];
        $grantedActions = array_map(
            fn (string $key) => PermissionMatrix::resolve($key),
            $grantedRaw
        );

        foreach ($grantedActions as $action) {
            if (! $allPermissions->has($action)) {
                Log::warning("SystemRolesSeeder: unknown permission action [{$action}] for role [{$role->slug}].");
            }
        }

        $permMap = [];
        foreach ($allPermissions->keys() as $action) {
            $permMap[$action] = in_array($action, $grantedActions, true);
        }
        $this->applyPermissions($role, $allPermissions, $permMap);
    }

    private function applyPermissions(Role $role, $allPermissions, array $permissionMap): void
    {
        foreach ($permissionMap as $action => $isGranted) {
            if (! $allPermissions->has($action)) {
                continue;
            }
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'permission_id' => $allPermissions[$action]->id],
                ['is_granted' => (bool) $isGranted]
            );
        }
    }
}
