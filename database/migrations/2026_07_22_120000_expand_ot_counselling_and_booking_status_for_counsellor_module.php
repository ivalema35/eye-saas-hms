<?php

/**
 * 2026_07_22_120000_expand_ot_counselling_and_booking_status_for_counsellor_module.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 1 (Counsellor Module).
 *          `ot_counselling` was previously a thin table auto-filled by the receptionist
 *          at booking time (mediclaim, lens_option, package_amount, payment_mode, report_ok, notes).
 *          This expands it into the full Counsellor screen data model (diagnosis, lens
 *          selection, package cost breakdown, blood-report checks) per docs/OT_WORKFLOW_UPGRADE_PRD.md §1.
 *
 *          Also inserts two new `ot_bookings.ot_status` enum values between `booked` and `paid`:
 *          `surgery_recommended` (set by doctor — future phase) and `counselled` (set by
 *          Counsellor once counselling + consent are done, before Billing/Accountant can act).
 *
 * TENANT-SCOPED: YES (ot_counselling already has tenant_id)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_counselling', function (Blueprint $table) {
            $table->string('diagnosis', 255)->nullable()->after('ot_booking_id');
            $table->boolean('surgery_type_confirmed')->default(false)->after('diagnosis');

            $table->string('lens_category', 20)->nullable()->after('lens_option'); // standard/premium
            $table->string('lens_company', 150)->nullable()->after('lens_category');
            $table->string('lens_model', 150)->nullable()->after('lens_company');
            $table->string('lens_type', 100)->nullable()->after('lens_model');
            $table->decimal('estimated_power', 5, 2)->nullable()->after('lens_type');
            $table->decimal('lens_cost', 10, 2)->nullable()->after('estimated_power');

            $table->string('package_name', 150)->nullable()->after('package_amount');
            $table->string('room_category', 20)->nullable()->after('package_name'); // general/private
            $table->decimal('ot_charges', 10, 2)->nullable()->after('room_category');
            $table->decimal('surgeon_charges', 10, 2)->nullable()->after('ot_charges');
            $table->decimal('nursing_charges', 10, 2)->nullable()->after('surgeon_charges');
            $table->decimal('consumables_charges', 10, 2)->nullable()->after('nursing_charges');
            $table->decimal('total_estimate', 10, 2)->nullable()->after('consumables_charges');

            $table->boolean('blood_reports_verified')->default(false)->after('report_ok');
            $table->boolean('blood_reports_normal')->default(false)->after('blood_reports_verified');

            $table->foreignId('counselled_by')->nullable()->after('notes')->constrained('hospital_users')->nullOnDelete();
            $table->timestamp('counselled_at')->nullable()->after('counselled_by');
        });

        DB::statement("ALTER TABLE ot_bookings MODIFY COLUMN ot_status ENUM(
            'booked', 'surgery_recommended', 'counselled', 'paid', 'in_ward', 'dilated',
            'ready', 'complicated', 'operated', 'discharged', 'cancelled'
        ) NOT NULL DEFAULT 'booked'");
    }

    public function down(): void
    {
        // Revert any rows using the new statuses before shrinking the enum, else the ALTER fails.
        DB::table('ot_bookings')
            ->whereIn('ot_status', ['surgery_recommended', 'counselled'])
            ->update(['ot_status' => 'booked']);

        DB::statement("ALTER TABLE ot_bookings MODIFY COLUMN ot_status ENUM(
            'booked', 'paid', 'in_ward', 'dilated', 'ready', 'complicated', 'operated', 'discharged', 'cancelled'
        ) NOT NULL DEFAULT 'booked'");

        Schema::table('ot_counselling', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counselled_by');
            $table->dropColumn([
                'diagnosis', 'surgery_type_confirmed',
                'lens_category', 'lens_company', 'lens_model', 'lens_type', 'estimated_power', 'lens_cost',
                'package_name', 'room_category', 'ot_charges', 'surgeon_charges', 'nursing_charges',
                'consumables_charges', 'total_estimate',
                'blood_reports_verified', 'blood_reports_normal',
                'counselled_at',
            ]);
        });
    }
};
