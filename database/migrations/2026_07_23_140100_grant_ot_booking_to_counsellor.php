<?php

/**
 * Grant counsellor OT surgery booking (PDF: counsellor books OT for walk-ins).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $actions = ['ot.booking.create', 'ot.booking.modify', 'ot.patient.list'];

        $permissionIds = DB::table('permissions')
            ->whereIn('action', $actions)
            ->pluck('id', 'action');

        $roleIds = DB::table('roles')->where('slug', 'ot_counsellor')->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => (int) $roleId,
                        'permission_id' => (int) $permissionId,
                    ],
                    [
                        'is_granted' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('action', ['ot.booking.create', 'ot.booking.modify'])
            ->pluck('id');

        $roleIds = DB::table('roles')->where('slug', 'ot_counsellor')->pluck('id');

        DB::table('role_permissions')
            ->whereIn('role_id', $roleIds)
            ->whereIn('permission_id', $permissionIds)
            ->update(['is_granted' => false, 'updated_at' => now()]);
    }
};
