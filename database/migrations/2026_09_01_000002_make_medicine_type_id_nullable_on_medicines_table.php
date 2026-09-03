<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('medicines', 'medicine_type_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('medicines', function (Blueprint $table) {
            $table->dropForeign(['medicine_type_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE medicines MODIFY medicine_type_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('medicines', function (Blueprint $table) {
                $table->unsignedBigInteger('medicine_type_id')->nullable()->change();
            });
        }

        Schema::table('medicines', function (Blueprint $table) {
            $table->foreign('medicine_type_id')
                ->references('id')
                ->on('medicine_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('medicines', 'medicine_type_id')) {
            return;
        }

        $fallbackTypeId = DB::table('medicine_types')->orderBy('id')->value('id');
        if ($fallbackTypeId) {
            DB::table('medicines')
                ->whereNull('medicine_type_id')
                ->update(['medicine_type_id' => $fallbackTypeId]);
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('medicines', function (Blueprint $table) {
            $table->dropForeign(['medicine_type_id']);
        });

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE medicines MODIFY medicine_type_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('medicines', function (Blueprint $table) {
                $table->unsignedBigInteger('medicine_type_id')->nullable(false)->change();
            });
        }

        Schema::table('medicines', function (Blueprint $table) {
            $table->foreign('medicine_type_id')
                ->references('id')
                ->on('medicine_types');
        });
    }
};
