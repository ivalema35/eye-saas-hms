<?php

/**
 * Phase 3 — Fix role slugs and drop legacy user tables.
 *
 * 1. Fix role slug: 'reception' → 'receptionist' (per docx spec)
 * 2. Remove 'super_admin' role from tenant roles (platform-level only)
 * 3. Drop legacy tables: hospital_admins, doctors, receptions, ot_staff
 *    (all FKs already repointed to hospital_users in Phase 2)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix role slug: reception → receptionist
        DB::table('roles')
            ->where('slug', 'reception')
            ->update([
                'slug' => 'receptionist',
                'name' => 'Receptionist (OPD)',
            ]);

        // 2. Remove super_admin role from tenant roles (it's a platform concept, not tenant)
        DB::table('roles')
            ->where('slug', 'super_admin')
            ->delete();

        // 3. Drop legacy user tables (no FK references remain)
        Schema::dropIfExists('hospital_admins');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('receptions');
        Schema::dropIfExists('ot_staff');
    }

    public function down(): void
    {
        // Restore slug
        DB::table('roles')
            ->where('slug', 'receptionist')
            ->update([
                'slug' => 'reception',
                'name' => 'Receptionist (OPD)',
            ]);

        // Note: Dropped tables cannot be fully restored here.
        // Use the original migration files to recreate if needed.
    }
};
