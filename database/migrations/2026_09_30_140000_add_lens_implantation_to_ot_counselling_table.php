<?php

/**
 * 2026_09_30_140000_add_lens_implantation_to_ot_counselling_table.php
 *
 * PURPOSE: OT Assistant "C. Lens Selection" — Yes/No confirmation of whether
 *          the lens was actually implanted during surgery (distinct from
 *          `lens_option`, which is the IOL master pick made at counselling time).
 *
 * TENANT-SCOPED: YES (ot_counselling already has tenant_id)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_counselling', function (Blueprint $table) {
            $table->boolean('lens_implantation')->nullable()->after('lens_cost');
        });
    }

    public function down(): void
    {
        Schema::table('ot_counselling', function (Blueprint $table) {
            $table->dropColumn('lens_implantation');
        });
    }
};
