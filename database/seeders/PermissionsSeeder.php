<?php

namespace Database\Seeders;

use App\Models\Role\Permission;
use App\Models\Role\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PermissionsSeeder
 *
 * Master permissions list — platform-wide, no tenant_id.
 * These permissions represent specific feature access keys.
 *
 * Groups:
 *   - patient      Patient management
 *   - exam         Eye examination
 *   - ot           Operation Theatre
 *   - foc          Free of Charge management
 *   - medicine     Medicine master
 *   - report       Reports & analytics
 *   - master       Master data (districts, referrals, etc.)
 *   - setting      Hospital settings
 *   - role         Role & user management
 *   - subscription Subscription actions (admin-only)
 */
class PermissionsSeeder extends Seeder
{
    /**
     * List of all permissions.
     * action: unique machine-readable identifier (used as permission key in RolePermissionService)
     * label: human-readable label
     * module: logical grouping
     * description: what this permission controls
     */
    private array $permissions = [
        // ── OPD Module ──────────────────────────────────────────────
        ['module' => 'opd', 'action' => 'opd.patient.register',       'label' => 'Register Walk-in Patient'],
        ['module' => 'opd', 'action' => 'opd.patient.register_phone', 'label' => 'Register Phone Appointment'],
        ['module' => 'opd', 'action' => 'opd.patient.view',           'label' => "View Today's OPD Patients"],
        ['module' => 'opd', 'action' => 'opd.patient.edit',           'label' => 'Edit Patient Record (pre-exam)'],
        ['module' => 'opd', 'action' => 'opd.patient.delete',         'label' => 'Soft Delete Patient'],
        ['module' => 'opd', 'action' => 'opd.exam.primary',           'label' => 'Perform Primary Eye Exam'],
        ['module' => 'opd', 'action' => 'opd.exam.secondary',         'label' => 'Perform Secondary Eye Exam'],
        ['module' => 'opd', 'action' => 'opd.exam.history',           'label' => 'View Patient Exam History'],
        ['module' => 'opd', 'action' => 'opd.bill.print',             'label' => 'Print OPD Bill'],
        ['module' => 'opd', 'action' => 'opd.prescription.print',     'label' => 'Print Prescription'],
        ['module' => 'opd', 'action' => 'opd.foc.create',             'label' => 'Create FOC (Free Of Charge)'],
        ['module' => 'opd', 'action' => 'opd.foc.accept',             'label' => 'Accept FOC'],
        ['module' => 'opd', 'action' => 'opd.reports.view',           'label' => 'View OPD Reports'],
        ['module' => 'opd', 'action' => 'opd.reports.export',         'label' => 'Export OPD Data to Excel'],

        // ── OT Module ───────────────────────────────────────────────
        ['module' => 'ot', 'action' => 'ot.appointment.view',    'label' => 'View OT Appointments'],
        ['module' => 'ot', 'action' => 'ot.appointment.create',  'label' => 'Book OT Appointment (Pre-Registration)'],
        ['module' => 'ot', 'action' => 'ot.appointment.edit',    'label' => 'Edit/Confirm/Cancel OT Appointment'],
        ['module' => 'ot', 'action' => 'ot.booking.create',      'label' => 'Book OT Appointment'],
        ['module' => 'ot', 'action' => 'ot.booking.modify',      'label' => 'Modify OT Booking'],
        ['module' => 'ot', 'action' => 'ot.booking.cancel',      'label' => 'Cancel OT Booking'],
        ['module' => 'ot', 'action' => 'ot.counselling.fill',    'label' => 'Fill OT Counselling Form'],
        ['module' => 'ot', 'action' => 'ot.consent.capture',     'label' => 'Capture Patient Consent & Signature'],
        ['module' => 'ot', 'action' => 'ot.patient.list',        'label' => 'View OT Patient List'],
        ['module' => 'ot', 'action' => 'ot.package.set',         'label' => 'Set Package Amount'],
        ['module' => 'ot', 'action' => 'ot.payment.record',      'label' => 'Record Payment and Receipt'],
        ['module' => 'ot', 'action' => 'ot.payment.export',      'label' => 'Excel Export (OT Payments)'],
        ['module' => 'ot', 'action' => 'ot.ward.entry',          'label' => 'Ward Admission (Attend/Postpone)'],
        ['module' => 'ot', 'action' => 'ot.preop.entry',         'label' => 'Pre-Op Vitals Entry (BP/RBS etc.)'],
        ['module' => 'ot', 'action' => 'ot.dilation.track',      'label' => 'Dilation Drop Tracking'],
        ['module' => 'ot', 'action' => 'ot.surgery.recommend',   'label' => 'Recommend Surgery from Exam (Refer to Counsellor)'],
        ['module' => 'ot', 'action' => 'ot.surgery.ready',       'label' => 'Mark Ready to Operate'],
        ['module' => 'ot', 'action' => 'ot.surgery.record',      'label' => 'Record Surgery Details'],
        ['module' => 'ot', 'action' => 'ot.lens.record',         'label' => 'Record Lens Details'],
        ['module' => 'ot', 'action' => 'ot.lens.implant',        'label' => 'Mark Lens Implanted'],
        ['module' => 'ot', 'action' => 'ot.meds.takehome',       'label' => 'Add Take-Home Medicines'],
        ['module' => 'ot', 'action' => 'ot.invoice.view',        'label' => 'View Auto-Generated Invoice'],
        ['module' => 'ot', 'action' => 'ot.invoice.edit',        'label' => 'Edit Invoice Line Items'],
        ['module' => 'ot', 'action' => 'ot.discharge.generate',  'label' => 'Generate Discharge Documents'],
        ['module' => 'ot', 'action' => 'ot.discharge.patient',   'label' => 'Discharge Patient (Final)'],
        ['module' => 'ot', 'action' => 'ot.certificate.print',   'label' => 'Print Operation Certificate'],
        ['module' => 'ot', 'action' => 'ot.bill.print',          'label' => 'Print Bill of Summary'],
        ['module' => 'ot', 'action' => 'ot.billing.manage',      'label' => 'Manage OT Billing Documents'],

        // ── Masters ─────────────────────────────────────────────────
        ['module' => 'master', 'action' => 'master.case_types',  'label' => 'Case Types CRUD'],
        ['module' => 'master', 'action' => 'master.doctors',     'label' => 'Doctor Accounts CRUD'],
        ['module' => 'master', 'action' => 'master.receptions',  'label' => 'Receptionist Accounts CRUD'],
        ['module' => 'master', 'action' => 'master.ot_staff',    'label' => 'OT Staff Accounts CRUD'],
        ['module' => 'master', 'action' => 'master.roles',       'label' => 'Custom Role Management'],
        ['module' => 'master', 'action' => 'master.locations',   'label' => 'Locations Master CRUD'],
        ['module' => 'master', 'action' => 'master.medicines',   'label' => 'Medicine Master CRUD'],
        ['module' => 'master', 'action' => 'master.eye_exam',    'label' => 'Eye Exam Masters CRUD'],
        ['module' => 'master', 'action' => 'master.ot_slots',    'label' => 'OT Slot Master CRUD'],
        ['module' => 'master', 'action' => 'master.ot_types',    'label' => 'OT Type Master CRUD'],
        ['module' => 'master', 'action' => 'master.ot_charges',  'label' => 'OT Charge Heads CRUD'],
        ['module' => 'master', 'action' => 'master.ot_inventory', 'label' => 'OT Lens Inventory / Power / Package Masters CRUD'],

        // ── Settings ────────────────────────────────────────────────
        ['module' => 'setting', 'action' => 'settings.hospital',      'label' => 'Hospital Profile Settings'],
        ['module' => 'setting', 'action' => 'settings.subscription',  'label' => 'Subscription / Billing Info'],

        // ── Reports ─────────────────────────────────────────────────
        ['module' => 'report', 'action' => 'reports.view',    'label' => 'View All Reports'],
        ['module' => 'report', 'action' => 'reports.export',  'label' => 'Export All Reports'],
    ];

    public function run(): void
    {
        $this->command->info('Seeding permissions...');

        Schema::disableForeignKeyConstraints();
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($this->permissions as $permData) {
            Permission::firstOrCreate(
                ['action' => $permData['action']],
                [
                    'label' => $permData['label'],
                    'module' => $permData['module'],
                    'description' => $permData['description'] ?? '',
                    'sort_order' => 0,
                ]
            );
        }

        $this->syncSuperRolesWithAllPermissions();

        $this->command->info('Permissions seeded: '.count($this->permissions).' total.');
    }

    private function syncSuperRolesWithAllPermissions(): void
    {
        $superRoleIds = Role::query()->where('is_super', 1)->pluck('id');

        if ($superRoleIds->isEmpty()) {
            $this->command->warn('No super roles found; skipping role_permissions sync.');

            return;
        }

        $permissionIds = Permission::query()->pluck('id');

        $existing = DB::table('role_permissions')
            ->whereIn('role_id', $superRoleIds)
            ->select('role_id', 'permission_id')
            ->get()
            ->mapWithKeys(function ($row) {
                return ["{$row->role_id}:{$row->permission_id}" => true];
            });

        $timestamp = now();
        $rowsToInsert = [];

        foreach ($superRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $key = "{$roleId}:{$permissionId}";

                if (isset($existing[$key])) {
                    continue;
                }

                $rowsToInsert[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'is_granted' => 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if (! empty($rowsToInsert)) {
            DB::table('role_permissions')->insert($rowsToInsert);
        }

        $this->command->info('Super role permission sync completed. Added: '.count($rowsToInsert));
    }
}
