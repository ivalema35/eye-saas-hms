<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = (int) DB::table('permissions')->where('action', 'reports.export')->value('id');
        if ($permissionId <= 0) {
            return;
        }

        $now = now();
        $roleIds = DB::table('roles')
            ->whereIn('slug', ['receptionist', 'receptionist_opd'])
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
        $permissionId = DB::table('permissions')->where('action', 'reports.export')->value('id');
        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('slug', ['receptionist', 'receptionist_opd'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        DB::table('role_permissions')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', $roleIds)
            ->update(['is_granted' => false, 'updated_at' => now()]);
    }
};
