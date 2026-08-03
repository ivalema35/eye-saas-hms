<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doctor / assistant are assigned at Ward (Patient Status), not at Recommend Surgery.
        if (! Schema::hasColumn('ot_bookings', 'ot_doctor_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->dropForeign(['ot_doctor_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ot_bookings MODIFY ot_doctor_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('ot_bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('ot_doctor_id')->nullable()->change();
            });
        }

        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->foreign('ot_doctor_id')
                ->references('id')
                ->on('hospital_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ot_bookings', 'ot_doctor_id')) {
            return;
        }

        $fallbackDoctorId = DB::table('hospital_users')->orderBy('id')->value('id');
        if ($fallbackDoctorId) {
            DB::table('ot_bookings')
                ->whereNull('ot_doctor_id')
                ->update(['ot_doctor_id' => $fallbackDoctorId]);
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->dropForeign(['ot_doctor_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ot_bookings MODIFY ot_doctor_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('ot_bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('ot_doctor_id')->nullable(false)->change();
            });
        }

        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->foreign('ot_doctor_id')
                ->references('id')
                ->on('hospital_users');
        });
    }
};
