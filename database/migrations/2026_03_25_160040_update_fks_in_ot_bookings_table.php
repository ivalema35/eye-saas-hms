<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['ot_bookings_ot_doctor_id_foreign', 'ot_bookings_booked_by_foreign'] as $fk) {
            try {
                DB::statement("ALTER TABLE `ot_bookings` DROP FOREIGN KEY `{$fk}`");
            } catch (Exception $e) {
                // FK may already be dropped
            }
        }

        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->foreign('ot_doctor_id')->references('id')->on('hospital_users');
            $table->foreign('booked_by')->references('id')->on('hospital_users');
        });
    }

    public function down(): void
    {
        foreach (['ot_bookings_ot_doctor_id_foreign', 'ot_bookings_booked_by_foreign'] as $fk) {
            try {
                DB::statement("ALTER TABLE `ot_bookings` DROP FOREIGN KEY `{$fk}`");
            } catch (Exception $e) {
                // FK may already be dropped
            }
        }

        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->foreign('ot_doctor_id')->references('id')->on('ot_staff');
            $table->foreign('booked_by')->references('id')->on('ot_staff');
        });
    }
};
