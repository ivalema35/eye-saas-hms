<?php

/**
 * 2026_07_22_150000_expand_ot_pre_op_and_dilation_entries_for_ward_module.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 3 (Ward Module).
 *          `ot_pre_op` and `ot_dilation_entries` already existed in the schema but
 *          were never read/written by any controller. This expands them to match
 *          the PDF's Ward Preparation step (vitals incl. Oxygen Saturation, and a
 *          per-dose Eye Drop Register) and wires them up via OtWardController.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §3.
 *
 * TENANT-SCOPED: YES (both tables already have tenant_id)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_pre_op', function (Blueprint $table) {
            $table->string('pulse', 20)->nullable()->after('bp');
            $table->decimal('spo2', 5, 2)->nullable()->after('temperature');
        });

        // Rename pre_op_status enum values to match the PDF wording:
        // ready -> ready_for_surgery, complicated -> not_fit
        DB::statement("ALTER TABLE ot_pre_op MODIFY COLUMN pre_op_status ENUM('ready', 'complicated', 'ready_for_surgery', 'not_fit') NULL");
        DB::table('ot_pre_op')->where('pre_op_status', 'ready')->update(['pre_op_status' => 'ready_for_surgery']);
        DB::table('ot_pre_op')->where('pre_op_status', 'complicated')->update(['pre_op_status' => 'not_fit']);
        DB::statement("ALTER TABLE ot_pre_op MODIFY COLUMN pre_op_status ENUM('ready_for_surgery', 'not_fit') NULL");

        Schema::table('ot_dilation_entries', function (Blueprint $table) {
            $table->enum('eye', ['RE', 'LE'])->nullable()->after('medicine_name');
            $table->unsignedTinyInteger('dose_number')->nullable()->after('eye');
            $table->foreignId('administered_by')->nullable()->after('is_instilled')->constrained('hospital_users')->nullOnDelete();
            $table->text('remarks')->nullable()->after('administered_by');
        });
    }

    public function down(): void
    {
        Schema::table('ot_dilation_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('administered_by');
            $table->dropColumn(['eye', 'dose_number', 'remarks']);
        });

        DB::statement("ALTER TABLE ot_pre_op MODIFY COLUMN pre_op_status ENUM('ready', 'complicated', 'ready_for_surgery', 'not_fit') NULL");
        DB::table('ot_pre_op')->where('pre_op_status', 'ready_for_surgery')->update(['pre_op_status' => 'ready']);
        DB::table('ot_pre_op')->where('pre_op_status', 'not_fit')->update(['pre_op_status' => 'complicated']);
        DB::statement("ALTER TABLE ot_pre_op MODIFY COLUMN pre_op_status ENUM('ready', 'complicated') NULL");

        Schema::table('ot_pre_op', function (Blueprint $table) {
            $table->dropColumn(['pulse', 'spo2']);
        });
    }
};
