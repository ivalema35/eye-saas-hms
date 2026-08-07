<?php

/**
 * Add surgery_refused to ot_bookings.ot_status.
 * Doctor consult refuses OT after ward Hold/Preparing/Complicated → Account for refund (Phase 3).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ot_bookings MODIFY COLUMN ot_status ENUM(
            'booked', 'surgery_recommended', 'counselled', 'paid', 'payment_verified', 'in_ward', 'dilated',
            'ready', 'complicated', 'operated', 'discharged', 'cancelled', 'surgery_refused'
        ) NOT NULL DEFAULT 'booked'");
    }

    public function down(): void
    {
        DB::table('ot_bookings')
            ->where('ot_status', 'surgery_refused')
            ->update(['ot_status' => 'cancelled']);

        DB::statement("ALTER TABLE ot_bookings MODIFY COLUMN ot_status ENUM(
            'booked', 'surgery_recommended', 'counselled', 'paid', 'payment_verified', 'in_ward', 'dilated',
            'ready', 'complicated', 'operated', 'discharged', 'cancelled'
        ) NOT NULL DEFAULT 'booked'");
    }
};
