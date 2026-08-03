<?php

/**
 * 2026_07_22_180000_add_follow_up_date_to_ot_invoices.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 6 (Discharge Module).
 *          Backs the new Follow-up Appointment Slip document. `ot_invoices` is
 *          the shared discharge/invoice table (see OtDischargeSummary model
 *          docblock), so this one column serves both the invoice generation
 *          step and the follow-up slip print.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §6.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_invoices', function (Blueprint $table) {
            $table->date('follow_up_date')->nullable()->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('ot_invoices', function (Blueprint $table) {
            $table->dropColumn('follow_up_date');
        });
    }
};
