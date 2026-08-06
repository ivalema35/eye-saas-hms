<?php

/**
 * Round 3 mobile API — Lens Inventory / Lens Power / Package master endpoints.
 *
 * On the web, `masters/ot/{lens-inventory,lens-powers,packages}` are gated by
 * `middleware('role:admin')`, not a `permission:` key (routes/hospital.php ~line 348).
 * `CheckRole` hardcodes `auth('hospital_user')` — the session guard — so it cannot
 * authenticate a Sanctum bearer-token mobile request at all; reusing it on API
 * routes would 403 every mobile call. This migration adds a proper permission key
 * (`master.ot_inventory`, matching the existing `master.ot_slots`/`master.ot_types`/
 * `master.ot_charges` naming convention) and grants it to `hospital_admin`, mirroring
 * the exact pattern already used by 2026_07_23_130000_grant_ot_billing_manage_to_roles.
 *
 * Additive only — does not touch the web route's `role:admin` gate or change any
 * existing behavior for web logins.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['action' => 'master.ot_inventory'],
            [
                'module' => 'master',
                'label' => 'OT Lens Inventory / Power / Package Masters CRUD',
                'description' => 'Manage lens stock inventory, lens power master, and OT package master.',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $permissionId = (int) DB::table('permissions')->where('action', 'master.ot_inventory')->value('id');
        if ($permissionId <= 0) {
            return;
        }

        $roleIds = DB::table('roles')->where('slug', 'hospital_admin')->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => (int) $roleId, 'permission_id' => $permissionId],
                ['is_granted' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('action', 'master.ot_inventory')->value('id');
        if (! $permissionId) {
            return;
        }

        DB::table('role_permissions')->where('permission_id', $permissionId)->update(['is_granted' => false, 'updated_at' => now()]);
    }
};
