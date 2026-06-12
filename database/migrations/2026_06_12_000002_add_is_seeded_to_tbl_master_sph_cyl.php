<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_master_sph_cyl', function (Blueprint $table) {
            $table->boolean('is_seeded')->default(false)->after('value');
        });

        // Mark all standard values (0.25 to 10.00 in 0.25 steps) as seeded
        $standardValues = ['0'];
        for ($i = 0.25; $i <= 10.00; $i += 0.25) {
            $standardValues[] = number_format($i, 2);
        }

        DB::table('tbl_master_sph_cyl')
            ->whereIn('value', $standardValues)
            ->update(['is_seeded' => true]);
    }

    public function down(): void
    {
        Schema::table('tbl_master_sph_cyl', function (Blueprint $table) {
            $table->dropColumn('is_seeded');
        });
    }
};
