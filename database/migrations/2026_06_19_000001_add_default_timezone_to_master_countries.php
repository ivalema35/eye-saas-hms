<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_master_countries', function (Blueprint $table) {
            $table->string('default_timezone', 100)->default('UTC')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_master_countries', function (Blueprint $table) {
            $table->dropColumn('default_timezone');
        });
    }
};
