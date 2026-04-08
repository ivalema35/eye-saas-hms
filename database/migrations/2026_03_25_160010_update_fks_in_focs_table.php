<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('focs', function (Blueprint $table) {
            $table->dropForeign('focs_doctor_id_foreign');
            $table->dropForeign('focs_reception_id_foreign');
            $table->dropForeign('focs_accepted_by_foreign');

            $table->foreign('doctor_id')->references('id')->on('hospital_users');
            $table->foreign('reception_id')->references('id')->on('hospital_users');
            $table->foreign('accepted_by')->references('id')->on('hospital_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('focs', function (Blueprint $table) {
            $table->dropForeign('focs_doctor_id_foreign');
            $table->dropForeign('focs_reception_id_foreign');
            $table->dropForeign('focs_accepted_by_foreign');

            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('reception_id')->references('id')->on('receptions');
            $table->foreign('accepted_by')->references('id')->on('receptions');
        });
    }
};
