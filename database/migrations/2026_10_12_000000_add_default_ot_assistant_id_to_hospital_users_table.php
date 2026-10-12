<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_users', function (Blueprint $table) {
            $table->foreignId('default_ot_assistant_id')->nullable()->after('doctor_prefix')
                ->constrained('hospital_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hospital_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_ot_assistant_id');
        });
    }
};
