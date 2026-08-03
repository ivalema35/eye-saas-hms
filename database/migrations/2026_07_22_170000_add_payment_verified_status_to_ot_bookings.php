<?php

/**
 * 2026_07_22_170000_add_payment_verified_status_to_ot_bookings.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 5 (Billing / Payment Enhancements).
 *          PDF Step 6: after Billing collects payment, the Counsellor re-verifies
 *          payment before the patient can go to Ward. Inserts `payment_verified`
 *          between `paid` and `in_ward` in the ot_status enum.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §5.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ot_bookings MODIFY COLUMN ot_status ENUM(
            'booked', 'surgery_recommended', 'counselled', 'paid', 'payment_verified', 'in_ward', 'dilated',
            'ready', 'complicated', 'operated', 'discharged', 'cancelled'
        ) NOT NULL DEFAULT 'booked'");
    }

    public function down(): void
    {
        DB::table('ot_bookings')
            ->where('ot_status', 'payment_verified')
            ->update(['ot_status' => 'paid']);

        DB::statement("ALTER TABLE ot_bookings MODIFY COLUMN ot_status ENUM(
            'booked', 'surgery_recommended', 'counselled', 'paid', 'in_ward', 'dilated',
            'ready', 'complicated', 'operated', 'discharged', 'cancelled'
        ) NOT NULL DEFAULT 'booked'");
    }
};
