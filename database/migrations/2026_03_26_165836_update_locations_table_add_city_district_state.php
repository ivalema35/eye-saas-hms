<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_locations', 'city')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->string('city')->nullable()->after('tenant_id');
            });
        }

        if (! Schema::hasColumn('tbl_locations', 'district')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->string('district')->nullable()->after('city');
            });
        }

        if (! Schema::hasColumn('tbl_locations', 'state')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->string('state')->nullable()->after('district');
            });
        }

        if (Schema::hasColumn('tbl_locations', 'name') && Schema::hasColumn('tbl_locations', 'city')) {
            DB::statement("UPDATE tbl_locations SET city = name WHERE city IS NULL OR city = ''");
        }

        if (Schema::hasColumn('tbl_locations', 'name')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        Schema::table('tbl_locations', function (Blueprint $table) {
            $table->unique(['tenant_id', 'city'], 'tbl_locations_tenant_city_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_locations', function (Blueprint $table) {
            $table->dropUnique('tbl_locations_tenant_city_unique');
        });

        if (Schema::hasColumn('tbl_locations', 'city')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->dropColumn('city');
            });
        }

        if (Schema::hasColumn('tbl_locations', 'district')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->dropColumn('district');
            });
        }

        if (Schema::hasColumn('tbl_locations', 'state')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->dropColumn('state');
            });
        }

        if (! Schema::hasColumn('tbl_locations', 'name')) {
            Schema::table('tbl_locations', function (Blueprint $table) {
                $table->string('name')->after('tenant_id');
            });
        }
    }
};
