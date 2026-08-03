<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_appointments', function (Blueprint $table) {
            $table->dropColumn('referred_by');
            $table->foreignId('referrer_id')->nullable()->after('appointment_type')
                ->constrained('tbl_referrers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ot_appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referrer_id');
            $table->string('referred_by', 255)->nullable()->after('appointment_type');
        });
    }
};
