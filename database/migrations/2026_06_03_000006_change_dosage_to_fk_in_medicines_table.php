<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn('dosage');
        });

        Schema::table('medicines', function (Blueprint $table) {
            $table->foreignId('dosage_id')
                ->nullable()
                ->after('name')
                ->constrained('dosages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropForeign(['dosage_id']);
            $table->dropColumn('dosage_id');
            $table->string('dosage', 100)->nullable()->after('name');
        });
    }
};
