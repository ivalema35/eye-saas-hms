<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Receptionist no longer sees the "OT / Surgery" sidebar group or
 * "Discharge & Invoices" — revoke every OT permission that shows them.
 */
return new class extends Migration
{
    private const ACTIONS = [
        'ot_patient_list',
        'ot_payment_record',
        'ot_payment_export',
        'ot_invoice_view',
        'ot_invoice_edit',
        'ot_billing_manage',
        'ot_bill_print',
        'ot_ward_entry',
        'ot_surgery_ready',
        'ot_surgery_record',
        'ot_lens_record',
        'ot_lens_implant',
        'ot_discharge_generate',
        'ot_certificate_print',
    ];

    public function up(): void
    {
        $this->setGranted(false);
    }

    public function down(): void
    {
        // Restore only what the receptionist template granted before this change.
        $this->setGranted(true, ['ot_patient_list', 'ot_invoice_view', 'ot_bill_print']);
    }

    private function setGranted(bool $granted, array $actions = self::ACTIONS): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('action', $actions)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $roles = DB::table('roles')
            ->whereIn('slug', ['receptionist', 'receptionist_opd'])
            ->get(['id', 'tenant_id']);

        $now = now();

        foreach ($roles as $role) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => (int) $permissionId],
                    ['is_granted' => $granted, 'updated_at' => $now, 'created_at' => $now]
                );
            }

            Cache::forget('hms_perms_'.$role->tenant_id.'_'.$role->id);
        }
    }
};
