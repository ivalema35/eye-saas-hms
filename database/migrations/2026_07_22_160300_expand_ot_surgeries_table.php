<?php

/**
 * 2026_07_22_160300_expand_ot_surgeries_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 4 (OT Module Enhancements).
 *          Adds Assistant, OT Room, Start/End Time (Duration is computed via an
 *          accessor on the OtSurgery model, not stored), Blood Loss, and a link to
 *          the OT-scoped Medicine Group used to quick-fill `ward_medicines`.
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
        Schema::table('ot_surgeries', function (Blueprint $table) {
            $table->foreignId('assistant_id')->nullable()->after('operated_by')->constrained('hospital_users')->nullOnDelete();
            $table->string('ot_room', 100)->nullable()->after('surgery_name');
            $table->dateTime('start_time')->nullable()->after('surgery_at');
            $table->dateTime('end_time')->nullable()->after('start_time');
            $table->string('blood_loss', 100)->nullable()->after('complication');
            $table->foreignId('medicine_group_id')->nullable()->after('ward_medicines')->constrained('medicine_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ot_surgeries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assistant_id');
            $table->dropConstrainedForeignId('medicine_group_id');
            $table->dropColumn(['ot_room', 'start_time', 'end_time', 'blood_loss']);
        });
    }
};
