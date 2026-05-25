<?php

/**
 * Phase 3 — Fix role slugs and drop legacy user tables.
 *
 * 1. Fix role slug: 'reception' → 'receptionist' (per docx spec)
 * 2. Remove 'super_admin' role from tenant roles (platform-level only)
 * 3. Drop FK constraints referencing legacy tables
 * 4. Drop legacy tables: hospital_admins, doctors, receptions, ot_staff
 *    (FKs will be repointed to hospital_users in the 1600xx FK-update migrations)
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

        // 3. Drop FK constraints referencing legacy tables so they can be dropped
        $legacyFkNames = [
            'patients_doctor_id_foreign',
            'patients_reception_id_foreign',
            'focs_doctor_id_foreign',
            'focs_reception_id_foreign',
            'focs_accepted_by_foreign',
            'primary_examinations_doctor_id_foreign',
            'secondary_examinations_doctor_id_foreign',
            'ot_bookings_ot_doctor_id_foreign',
            'ot_bookings_booked_by_foreign',
            'ot_payments_recorded_by_foreign',
            'ot_pre_op_entered_by_foreign',
            'ot_surgeries_operated_by_foreign',
            'ot_lens_details_entered_by_foreign',
            'ot_invoices_generated_by_foreign',
        ];

        // Map FK name to table name
        $fkTableMap = [
            'patients_doctor_id_foreign' => 'patients',
            'patients_reception_id_foreign' => 'patients',
            'focs_doctor_id_foreign' => 'focs',
            'focs_reception_id_foreign' => 'focs',
            'focs_accepted_by_foreign' => 'focs',
            'primary_examinations_doctor_id_foreign' => 'primary_examinations',
            'secondary_examinations_doctor_id_foreign' => 'secondary_examinations',
            'ot_bookings_ot_doctor_id_foreign' => 'ot_bookings',
            'ot_bookings_booked_by_foreign' => 'ot_bookings',
            'ot_payments_recorded_by_foreign' => 'ot_payments',
            'ot_pre_op_entered_by_foreign' => 'ot_pre_op',
            'ot_surgeries_operated_by_foreign' => 'ot_surgeries',
            'ot_lens_details_entered_by_foreign' => 'ot_lens_details',
            'ot_invoices_generated_by_foreign' => 'ot_invoices',
        ];

        $commaList = "'".implode("','", $legacyFkNames)."'";

        // Check which FKs still exist via INFORMATION_SCHEMA
        $existingFks = DB::select("
            SELECT TABLE_NAME, CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND CONSTRAINT_NAME IN ({$commaList})
        ");

        foreach ($existingFks as $fk) {
            DB::statement("ALTER TABLE {$fk->TABLE_NAME} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
        }

        // 4. Drop legacy user tables
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
