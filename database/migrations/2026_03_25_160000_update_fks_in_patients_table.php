<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign('patients_doctor_id_foreign');
            $table->dropForeign('patients_reception_id_foreign');

            $table->foreign('doctor_id')->references('id')->on('hospital_users');
            $table->foreign('reception_id')->references('id')->on('hospital_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign('patients_doctor_id_foreign');
            $table->dropForeign('patients_reception_id_foreign');

            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('reception_id')->references('id')->on('receptions');
        });
    }
};
