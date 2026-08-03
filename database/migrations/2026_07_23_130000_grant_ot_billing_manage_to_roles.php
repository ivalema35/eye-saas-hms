<?php

/**
 * Phase C3 — ensure ot.billing.manage is granted to accountant + ot_doctor roles.
 * Permission row itself already exists from 2026_04_11_200000.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['action' => 'ot.billing.manage'],
            [
                'module' => 'ot',
                'label' => 'Manage OT Billing Documents',
                'description' => 'Generate and print OT billing / discharge document set.',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $permissionId = (int) DB::table('permissions')->where('action', 'ot.billing.manage')->value('id');
        if ($permissionId <= 0) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['accountant', 'ot_doctor', 'hospital_admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => (int) $roleId, 'permission_id' => $permissionId],
                ['is_granted' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        // Keep permission row; only revoke from non-admin if rolling back grant.
        $permissionId = DB::table('permissions')->where('action', 'ot.billing.manage')->value('id');
        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('slug', ['accountant', 'ot_doctor'])->pluck('id');
        DB::table('role_permissions')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', $roleIds)
            ->update(['is_granted' => false, 'updated_at' => now()]);
    }
};
