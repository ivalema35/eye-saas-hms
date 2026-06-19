<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_users', function (Blueprint $table) {
            $table->string('registration_no', 100)->nullable()->after('doctor_prefix')
                ->comment('Doctor registration number');
            $table->unsignedSmallInteger('experience_years')->nullable()->after('registration_no')
                ->comment('Doctor experience in years');
            $table->string('signature_path')->nullable()->after('experience_years')
                ->comment('Doctor signature image (<20KB)');
            $table->string('profile_photo_path')->nullable()->after('signature_path')
                ->comment('Doctor profile photo (<20KB)');
        });
    }

    public function down(): void
    {
        Schema::table('hospital_users', function (Blueprint $table) {
            $table->dropColumn(['registration_no', 'experience_years', 'signature_path', 'profile_photo_path']);
        });
    }
};
