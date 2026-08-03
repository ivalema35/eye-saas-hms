<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('ot_bookings', 'ot_assistant_id')) {
                $table->foreignId('ot_assistant_id')
                    ->nullable()
                    ->after('ot_doctor_id')
                    ->constrained('hospital_users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('ot_bookings', 'ot_assistant_id')) {
                $table->dropConstrainedForeignId('ot_assistant_id');
            }
        });
    }
};
