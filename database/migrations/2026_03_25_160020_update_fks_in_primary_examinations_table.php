<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('primary_examinations', function (Blueprint $table) {
            $table->dropForeign('primary_examinations_doctor_id_foreign');
            $table->foreign('doctor_id')->references('id')->on('hospital_users');
        });
    }

    public function down(): void
    {
        Schema::table('primary_examinations', function (Blueprint $table) {
            $table->dropForeign('primary_examinations_doctor_id_foreign');
            $table->foreign('doctor_id')->references('id')->on('doctors');
        });
    }
};
