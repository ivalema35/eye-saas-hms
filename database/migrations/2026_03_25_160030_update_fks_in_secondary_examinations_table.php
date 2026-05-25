<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE `secondary_examinations` DROP FOREIGN KEY `secondary_examinations_doctor_id_foreign`');
        } catch (Exception $e) {
            // FK may already be dropped
        }

        Schema::table('secondary_examinations', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('id')->on('hospital_users');
        });
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE `secondary_examinations` DROP FOREIGN KEY `secondary_examinations_doctor_id_foreign`');
        } catch (Exception $e) {
            // FK may already be dropped
        }

        Schema::table('secondary_examinations', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('id')->on('doctors');
        });
    }
};
