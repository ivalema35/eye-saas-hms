<?php

/**
 * 2026_07_22_190100_add_lens_inventory_id_to_ot_lens_details.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 7 (Inventory Module). Links a recorded
 *          lens-implant entry back to the specific stock unit it was drawn
 *          from, so stock can be decremented exactly once on implant.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §7.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->foreignId('lens_inventory_id')->nullable()->after('ot_booking_id')->constrained('lens_inventory')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lens_inventory_id');
        });
    }
};
