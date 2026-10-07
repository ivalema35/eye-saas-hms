<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ACTIONS = [
        'dashboard_ot',
        'ot_appointment_view',
        'ot_appointment_create',
        'ot_appointment_edit',
        'ot_appointment_confirm',
        'ot_appointment_cancel',
    ];

    public function up(): void
    {
        $this->setGranted(false);
    }

    public function down(): void
    {
        $this->setGranted(true);
    }

    private function setGranted(bool $granted): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('action', self::ACTIONS)
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
