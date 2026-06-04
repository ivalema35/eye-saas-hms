<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_users', function (Blueprint $table) {
            $table->string('doctor_prefix', 5)->nullable()->after('doctor_type');
        });
    }

    public function down(): void
    {
        Schema::table('hospital_users', function (Blueprint $table) {
            $table->dropColumn('doctor_prefix');
        });
    }
};
