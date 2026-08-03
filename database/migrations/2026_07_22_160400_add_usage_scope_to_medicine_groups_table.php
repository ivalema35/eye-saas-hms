<?php

/**
 * 2026_07_22_160400_add_usage_scope_to_medicine_groups_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 4 (OT Module Enhancements).
 *          PDF explicitly wants OT-only medicine groups separated from OPD ones:
 *          "we will create group in hospital for OT and all OT group will see
 *          here (not in opd also)". Rather than duplicating the medicine_groups
 *          table, add a scope flag and filter by it wherever groups are listed
 *          (OPD prescription picker vs. OT surgery form).
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §4.
 *
 * TENANT-SCOPED: YES (medicine_groups already has tenant_id)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_groups', function (Blueprint $table) {
            // Existing groups default to 'opd' — matches their current exclusive usage.
            $table->enum('usage_scope', ['opd', 'ot', 'both'])->default('opd')->after('diagnosis_id');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_groups', function (Blueprint $table) {
            $table->dropColumn('usage_scope');
        });
    }
};
