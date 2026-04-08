<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->dropForeign('ot_bookings_ot_doctor_id_foreign');
            $table->dropForeign('ot_bookings_booked_by_foreign');

            $table->foreign('ot_doctor_id')->references('id')->on('hospital_users');
            $table->foreign('booked_by')->references('id')->on('hospital_users');
        });
    }

    public function down(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->dropForeign('ot_bookings_ot_doctor_id_foreign');
            $table->dropForeign('ot_bookings_booked_by_foreign');

            $table->foreign('ot_doctor_id')->references('id')->on('ot_staff');
            $table->foreign('booked_by')->references('id')->on('ot_staff');
        });
    }
};
