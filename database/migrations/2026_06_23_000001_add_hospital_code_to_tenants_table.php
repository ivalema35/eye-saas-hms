<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('hospital_code', 3)->nullable()->unique()->after('slug');
        });

        // Backfill existing tenants — derive code from slug (same logic as old prefix())
        DB::table('tenants')->orderBy('id')->each(function ($tenant) {
            $base = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $tenant->slug), 0, 3));
            $base = strlen($base) < 3 ? str_pad($base, 3, 'X') : $base;

            $code = $base;
            $suffix = 2;
            while (DB::table('tenants')->where('hospital_code', $code)->where('id', '!=', $tenant->id)->exists()) {
                $code = substr($base, 0, 2) . $suffix;
                $suffix++;
            }

            DB::table('tenants')->where('id', $tenant->id)->update(['hospital_code' => $code]);
        });

        // Now make it NOT NULL
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('hospital_code', 3)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('hospital_code');
        });
    }
};
