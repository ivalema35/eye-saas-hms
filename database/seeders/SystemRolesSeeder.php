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
        ['name' => 'Accountant', 'slug' => 'accountant', 'color' => '#117A65', 'is_super' => false, 'is_system' => true],
        ['name' => 'Ward Management', 'slug' => 'ward_management', 'color' => '#7D3C98', 'is_super' => false, 'is_system' => true],
        ['name' => 'OT Assistant', 'slug' => 'ot_assistant', 'color' => '#CA6F1E', 'is_super' => false, 'is_system' => true],
        ['name' => 'Discharge Counter', 'slug' => 'discharge_counter', 'color' => '#2E86C1', 'is_super' => false, 'is_system' => true],
    ];

    private array $defaultPermissions = [
        'doctor' => [
            'master.case_types',
            'master.eye_exam',
            'master.medicines',
            'master.locations',
            'master.roles',
            'opd.patient.view',
            'opd.patient.edit',
            'opd.exam.primary',
            'opd.exam.secondary',
            'opd.exam.history',
            'opd.prescription.print',
            'opd.foc.create',
            'ot.surgery.recommend',
            'reports.view',
        ],
        // Reception — absorbs the old ot_receptionist + ot_counsellor roles (docs/tulsi.md §2):
        // registration (unchanged) + OT Appointment booking + OT Counselling, all under one desk.
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
            'ot.appointment.view',
            'ot.appointment.create',
            'ot.appointment.edit',
            'ot.booking.create',
            'ot.booking.modify',
            'ot.booking.cancel',
            'ot.counselling.fill',
            'ot.consent.capture',
            'ot.patient.list',
            'ot.package.set',
            'ot.payment.record',
            'ot.payment.export',
            'ot.invoice.view',
            'ot.bill.print',
        ],

        // ── OT Roles ──────────────────────────────────────────────────
        'accountant' => [
            'ot.patient.list',
            'ot.payment.record',
            'ot.payment.export',
            'ot.invoice.view',
            'ot.invoice.edit',
            'ot.billing.manage',
            'ot.bill.print',
            'reports.view',
            'reports.export',
        ],
        // Ward Management — new role (docs/tulsi.md §4). Split off the ward-half of the old
        // ot_assistant role: pre-op vitals + eye-drop register. Receives the patient
        // automatically once Accountant marks payment verified; forwards to OT Assistant
        // on "Ready for OT".
        'ward_management' => [
            'ot.patient.list',
            'ot.ward.entry',
            'ot.preop.entry',
            'ot.dilation.track',
        ],
        // OT Assistant — keeps its lens-recording job and absorbs the old ot_doctor role's
        // Surgery Recording Form (docs/tulsi.md §5). Loses ward/pre-op/dilation permissions
        // to the new Ward Management role above.
        'ot_assistant' => [
            'ot.patient.list',
            'ot.surgery.ready',
            'ot.surgery.record',
            'ot.lens.record',
            'ot.lens.implant',
            'ot.meds.takehome',
        ],
        // Discharge Counter — new role (docs/tulsi.md §6). Owns "Discharge & Invoices" —
        // the discharge/billing slice previously carried by the old ot_doctor role.
        'discharge_counter' => [
            'ot.invoice.view',
            'ot.invoice.edit',
            'ot.billing.manage',
            'ot.discharge.generate',
            'ot.discharge.patient',
            'ot.certificate.print',
            'ot.bill.print',
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
            if (!$allPermissions->has($action)) {
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
            if (!$allPermissions->has($action)) {
                continue;
            }
            RolePermission::updateOrCreate(
                ['role_id' => $role->id, 'permission_id' => $allPermissions[$action]->id],
                ['is_granted' => (bool) $isGranted]
            );
        }
    }
}
