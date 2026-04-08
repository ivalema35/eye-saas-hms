<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_master_sph_cyl', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_master_sph_cyl', 'type')) {
                $table->string('type')->default('Positive')->after('tenant_id');
            }

            if (! Schema::hasColumn('tbl_master_sph_cyl', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_master_sph_cyl', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_master_sph_cyl', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('tbl_master_sph_cyl', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
