<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedSmallInteger('doctor_patient_no')->nullable()->after('patient_code');
            $table->index(['doctor_id', 'appointment_date', 'tenant_id'], 'patients_dr_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_dr_date_idx');
            $table->dropColumn('doctor_patient_no');
        });
    }
};
