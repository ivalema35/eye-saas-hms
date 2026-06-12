<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows per (tenant_id, value) — keep the lowest id
        DB::statement('
            DELETE t1 FROM tbl_master_sph_cyl t1
            INNER JOIN tbl_master_sph_cyl t2
                ON t1.tenant_id = t2.tenant_id
                AND t1.value = t2.value
                AND t1.id > t2.id
        ');

        Schema::table('tbl_master_sph_cyl', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_master_sph_cyl', function (Blueprint $table) {
            $table->string('type')->default('Positive')->after('tenant_id');
        });
    }
};
