<?php

namespace Database\Seeders;

use App\Models\Role\Permission;
use App\Models\Role\Role;
use App\Models\Role\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SystemRolesSeeder extends Seeder
{
    private array $systemRoles = [
        ['name' => 'Hospital Admin', 'slug' => 'hospital_admin', 'color' => '#1B4F72', 'is_super' => true, 'is_system' => true],
        ['name' => 'Doctor', 'slug' => 'doctor', 'color' => '#2980B9', 'is_super' => false, 'is_system' => false],
        ['name' => 'Receptionist', 'slug' => 'receptionist', 'color' => '#27AE60', 'is_super' => false, 'is_system' => false],
        ['name' => 'OT Receptionist', 'slug' => 'ot_receptionist', 'color' => '#7D3C98', 'is_super' => false, 'is_system' => true],
        ['name' => 'Accountant', 'slug' => 'accountant', 'color' => '#117A65', 'is_super' => false, 'is_system' => true],
        ['name' => 'OT Doctor', 'slug' => 'ot_doctor', 'color' => '#2E86C1', 'is_super' => false, 'is_system' => true],
        ['name' => 'OT Assistant', 'slug' => 'ot_assistant', 'color' => '#CA6F1E', 'is_super' => false, 'is_system' => true],
    ];

    private array $defaultPermissions = [
        'doctor' => [
            'master.case_types',
            'master.eye_exam',
            'master.medicines',
            'master.locations',
            'master.roles',
            'opd.patient.view',
            'opd.exam.primary',
            'opd.exam.secondary',
            'opd.exam.history',
            'opd.prescription.print',
            'opd.foc.create',
            'reports.view',
        ],
        'receptionist' => [
            'opd.patient.register',
            'opd.patient.register_phone',
            'opd.patient.view',
            'opd.patient.edit',
            'opd.patient.delete',
            'opd.bill.print',
            'opd.foc.accept',
            'reports.view',
            'master.receptions',
            'master.locations',
        ],

        // ── OT Roles ──────────────────────────────────────────────────
        'ot_receptionist' => [
            'ot.booking.create',
            'ot.booking.modify',
            'ot.booking.cancel',
            'ot.counselling.fill',
            'ot.patient.list',
            'ot.package.set',
            'ot.payment.record',
            'ot.payment.export',
            'ot.invoice.view',
            'ot.bill.print',
            'reports.view',
        ],
        'accountant' => [
            'ot.patient.list',
            'ot.payment.record',
            'ot.payment.export',
            'ot.invoice.view',
            'ot.invoice.edit',
            'ot.bill.print',
            'reports.view',
            'reports.export',
        ],
        'ot_doctor' => [
            'ot.patient.list',
            'ot.surgery.ready',
            'ot.surgery.record',
            'ot.lens.record',
            'ot.lens.implant',
            'ot.meds.takehome',
            'ot.invoice.view',
            'ot.discharge.generate',
            'ot.discharge.patient',
            'ot.certificate.print',
            'reports.view',
        ],
        'ot_assistant' => [
            'ot.patient.list',
            'ot.ward.entry',
            'ot.preop.entry',
            'ot.dilation.track',
            'ot.surgery.ready',
            'ot.lens.record',
            'ot.meds.takehome',
        ],
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
            // FIX: withoutTenantScope() use karo —
            // Public context (registration) mein config('app.tenant_id') = 0 hota hai.
            // Bina scope ke firstOrCreate ka first() part conflict karta tha.
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
            // Hospital Admin — all permissions granted
            $this->applyPermissions(
                $role,
                $allPermissions,
                array_fill_keys($allPermissions->keys()->toArray(), true)
            );

            return;
        }

        $grantedActions = $this->defaultPermissions[$role->slug] ?? [];
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
