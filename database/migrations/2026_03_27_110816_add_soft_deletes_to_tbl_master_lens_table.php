<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_master_lens', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_master_lens', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_master_lens', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_master_lens', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
