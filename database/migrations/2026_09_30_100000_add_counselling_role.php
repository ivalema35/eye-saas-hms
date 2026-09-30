<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSIONS = [
        'dashboard_ot',
        'ot_patient_list',
        'ot_counselling_fill',
        'ot_consent_capture',
        'ot_package_set',
    ];

    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('action', self::PERMISSIONS)
            ->pluck('id');

        $now = now();

        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            $existingId = DB::table('roles')
                ->where('tenant_id', $tenantId)
                ->where('slug', 'counselling')
                ->value('id');

            if ($existingId) {
                $roleId = (int) $existingId;
            } else {
                $roleId = DB::table('roles')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => 'Counselling',
                    'slug' => 'counselling',
                    'is_system' => true,
                    'is_super' => false,
                    'color' => '#D68910',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => (int) $permissionId],
                    ['is_granted' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $roles = DB::table('roles')->where('slug', 'counselling')->get(['id', 'tenant_id']);
        if ($roles->isEmpty()) {
            return;
        }

        foreach ($roles as $role) {
            $receptionistId = DB::table('roles')
                ->where('tenant_id', $role->tenant_id)
                ->where('slug', 'receptionist')
                ->value('id');

            if ($receptionistId) {
                DB::table('hospital_users')
                    ->where('role_id', $role->id)
                    ->update(['role_id' => $receptionistId]);
            }
        }

        $roleIds = $roles->pluck('id');
        DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('hospital_users')
                ->whereColumn('hospital_users.role_id', 'roles.id');
        })->delete();
    }
};
