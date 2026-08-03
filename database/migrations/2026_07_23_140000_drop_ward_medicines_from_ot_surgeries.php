<?php

/**
 * Final cleanup: drop legacy ot_surgeries.ward_medicines JSON.
 * Pivot ot_surgery_medicines is the source of truth (backfilled earlier).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ot_surgeries', 'ward_medicines')) {
            Schema::table('ot_surgeries', function (Blueprint $table) {
                $table->dropColumn('ward_medicines');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ot_surgeries', 'ward_medicines')) {
            Schema::table('ot_surgeries', function (Blueprint $table) {
                $table->json('ward_medicines')->nullable()->after('complication_notes');
            });
        }
    }
};
