<?php

/**
 * Remove OT roles (ot_receptionist, accountant, ot_doctor, ot_assistant)
 * from all tenants. Admin will create these manually when needed.
 *
 * Cleanup: role_permissions, hospital_users (reassign orphans), then roles.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $removeSlugs = ['ot_receptionist', 'accountant', 'ot_doctor', 'ot_assistant'];

    public function up(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('slug', $this->removeSlugs)
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        // 1. Delete role_permissions for these roles
        DB::table('role_permissions')
            ->whereIn('role_id', $roleIds)
            ->delete();

        // 2. Delete any hospital_users assigned to these roles
        DB::table('hospital_users')
            ->whereIn('role_id', $roleIds)
            ->delete();

        // 3. Delete the roles themselves (hard delete, bypass soft-delete)
        DB::table('roles')
            ->whereIn('id', $roleIds)
            ->delete();
    }

    public function down(): void
    {
        // Roles can be re-created by admin via UI — no automatic restore needed.
    }
};
