<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tbl_master_disc', 'tbl_master_fr'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('is_favourite')->default(false)->after('value');
            });
        }
    }

    public function down(): void
    {
        foreach (['tbl_master_disc', 'tbl_master_fr'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('is_favourite');
            });
        }
    }
};
