<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_master_advice', function (Blueprint $table) {
            $table->foreignId('diagnosis_id')
                ->nullable()
                ->after('value')
                ->constrained('tbl_master_diagnosis')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_master_advice', function (Blueprint $table) {
            $table->dropForeign(['diagnosis_id']);
            $table->dropColumn('diagnosis_id');
        });
    }
};
