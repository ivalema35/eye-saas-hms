<?php

/**
 * 2026_07_22_160100_expand_ot_lens_details_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 4 (OT Module Enhancements).
 *          `lens_type` is already a plain string(150) column (fixed in an earlier
 *          migration, 2026_04_11_123310) — NOT the enum('A','B') placeholder the
 *          original 2025 migration created, so no type change needed here. This
 *          migration only ADDS the PDF's remaining Lens Details fields.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §4.
 *
 * TENANT-SCOPED: YES
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->string('manufacturer', 150)->nullable()->after('lens_name');
            $table->decimal('axis', 5, 2)->nullable()->after('lens_power');
            $table->string('batch_number', 100)->nullable()->after('lens_mrp');
            $table->string('serial_number', 100)->nullable()->after('batch_number');
            $table->date('expiry_date')->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->dropColumn(['manufacturer', 'axis', 'batch_number', 'serial_number', 'expiry_date']);
        });
    }
};
