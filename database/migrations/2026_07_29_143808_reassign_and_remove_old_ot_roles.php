<?php

/**
 * OT role re-architecture — Phase 1 (docs/tulsi.md).
 *
 * Retires ot_receptionist, ot_counsellor, ot_doctor. For every tenant that has
 * these roles: reassign any hospital_users off them onto the role that now
 * absorbs their work (ot_receptionist/ot_counsellor -> receptionist,
 * ot_doctor -> ot_assistant), then hard-delete the old role rows so they stop
 * showing up as assignable options. Finally re-run SystemRolesSeeder for the
 * tenant so it picks up the new ward_management/discharge_counter roles and
 * the expanded receptionist/ot_assistant permission grants.
 *
 * If a tenant is missing the target role (receptionist/ot_assistant should
 * always exist, but don't assume it), the old role is left in place for that
 * tenant rather than orphaning its users.
 */

use Database\Seeders\SystemRolesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $roleReassignments = [
        'ot_receptionist' => 'receptionist',
        'ot_counsellor' => 'receptionist',
        'ot_doctor' => 'ot_assistant',
    ];

    public function up(): void
    {
        $tenantIds = DB::table('roles')
            ->whereIn('slug', array_keys($this->roleReassignments))
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            $rolesForTenant = DB::table('roles')
                ->where('tenant_id', $tenantId)
                ->whereIn('slug', array_merge(array_keys($this->roleReassignments), array_values($this->roleReassignments)))
                ->get(['id', 'slug'])
                ->keyBy('slug');

            $roleIdsToDelete = [];

            foreach ($this->roleReassignments as $oldSlug => $newSlug) {
                $oldRole = $rolesForTenant->get($oldSlug);

                if (! $oldRole) {
                    continue;
                }

                $newRole = $rolesForTenant->get($newSlug);

                if (! $newRole) {
                    // Target role missing for this tenant — leave the old role in
                    // place rather than orphaning its users.
                    continue;
                }

                DB::table('hospital_users')
                    ->where('tenant_id', $tenantId)
                    ->where('role_id', $oldRole->id)
                    ->update(['role_id' => $newRole->id]);

                $roleIdsToDelete[] = $oldRole->id;
            }

            if (empty($roleIdsToDelete)) {
                continue;
            }

            DB::table('role_permissions')->whereIn('role_id', $roleIdsToDelete)->delete();
            DB::table('roles')->whereIn('id', $roleIdsToDelete)->delete();

            SystemRolesSeeder::seedForTenant((int) $tenantId);
        }
    }

    public function down(): void
    {
        // Users were reassigned live and old roles hard-deleted — no automatic
        // restore. Old roles can be re-created manually via SystemRolesSeeder
        // if ever needed.
    }
};
