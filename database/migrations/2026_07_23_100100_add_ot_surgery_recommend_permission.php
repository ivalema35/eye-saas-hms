<?php

/**
 * OT 1.0 Remaining PRD — Phase A1.
 * Permission: ot.surgery.recommend — Doctor Exam → Surgery Recommended handoff.
 * Granted by default to doctor + ot_doctor roles (all tenants).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['action' => 'ot.surgery.recommend'],
            [
                'module' => 'ot',
                'label' => 'Recommend Surgery from Exam (Refer to Counsellor)',
                'description' => 'Create/update OT booking as surgery_recommended from OPD exam.',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $permissionId = (int) DB::table('permissions')
            ->where('action', 'ot.surgery.recommend')
            ->value('id');

        if ($permissionId <= 0) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['doctor', 'ot_doctor', 'hospital_admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => (int) $roleId,
                    'permission_id' => $permissionId,
                ],
                [
                    'is_granted' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('action', 'ot.surgery.recommend')
            ->value('id');

        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
