<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_groups', function (Blueprint $table) {
            $table->string('group_code', 50)->nullable()->after('name');
        });

        Schema::table('medicine_group_items', function (Blueprint $table) {
            $table->foreignId('route_id')
                ->nullable()
                ->after('dosage_id')
                ->constrained('medicine_routes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('medicine_group_items', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropColumn('route_id');
        });

        Schema::table('medicine_groups', function (Blueprint $table) {
            $table->dropColumn('group_code');
        });
    }
};
