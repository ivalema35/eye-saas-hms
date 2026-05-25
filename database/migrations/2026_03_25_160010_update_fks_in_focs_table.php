<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['focs_doctor_id_foreign', 'focs_reception_id_foreign', 'focs_accepted_by_foreign'] as $fk) {
            try {
                DB::statement("ALTER TABLE `focs` DROP FOREIGN KEY `{$fk}`");
            } catch (Exception $e) {
                // FK may already be dropped
            }
        }

        Schema::table('focs', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('id')->on('hospital_users');
            $table->foreign('reception_id')->references('id')->on('hospital_users');
            $table->foreign('accepted_by')->references('id')->on('hospital_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['focs_doctor_id_foreign', 'focs_reception_id_foreign', 'focs_accepted_by_foreign'] as $fk) {
            try {
                DB::statement("ALTER TABLE `focs` DROP FOREIGN KEY `{$fk}`");
            } catch (Exception $e) {
                // FK may already be dropped
            }
        }

        Schema::table('focs', function (Blueprint $table) {
            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('reception_id')->references('id')->on('receptions');
            $table->foreign('accepted_by')->references('id')->on('receptions');
        });
    }
};
