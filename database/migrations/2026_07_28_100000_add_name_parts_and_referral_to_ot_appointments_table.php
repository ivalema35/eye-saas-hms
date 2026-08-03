<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_appointments', function (Blueprint $table) {
            $table->string('middle_name', 200)->nullable()->after('patient_name');
            $table->string('surname', 200)->nullable()->after('middle_name');
            $table->string('occupation', 100)->nullable()->after('gender');
            $table->string('referred_by', 255)->nullable()->after('appointment_type');
        });
    }

    public function down(): void
    {
        Schema::table('ot_appointments', function (Blueprint $table) {
            $table->dropColumn(['middle_name', 'surname', 'occupation', 'referred_by']);
        });
    }
};
