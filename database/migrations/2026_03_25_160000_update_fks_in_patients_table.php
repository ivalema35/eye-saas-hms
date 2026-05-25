<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safely drop old FKs referencing legacy tables
        foreach (['patients_doctor_id_foreign', 'patients_reception_id_foreign'] as $fk) {
            try {
                DB::statement("ALTER TABLE `patients` DROP FOREIGN KEY `{$fk}`");
            } catch (Exception $e) {
                // FK may already be dropped — that's fine
            }
        }

        // Add new FKs pointing to hospital_users
        Schema::table('patients', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('id')->on('hospital_users');
            $table->foreign('reception_id')->references('id')->on('hospital_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['patients_doctor_id_foreign', 'patients_reception_id_foreign'] as $fk) {
            try {
                DB::statement("ALTER TABLE `patients` DROP FOREIGN KEY `{$fk}`");
            } catch (Exception $e) {
                // FK may already be dropped
            }
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('reception_id')->references('id')->on('receptions');
        });
    }
};
