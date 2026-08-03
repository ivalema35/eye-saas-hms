<?php

/**
 * OT 1.0 Remaining PRD — Phase A3.
 * Expand ot_pre_op.pre_op_status to match PDF Ward statuses:
 * Preparing / Ready for OT / Hold / Complicated.
 * Keeps legacy not_fit (maps to Complicated in UI).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ot_pre_op MODIFY COLUMN pre_op_status ENUM(
            'preparing',
            'ready_for_surgery',
            'hold',
            'complicated',
            'not_fit'
        ) NULL");
    }

    public function down(): void
    {
        DB::table('ot_pre_op')
            ->whereIn('pre_op_status', ['preparing', 'hold', 'complicated'])
            ->update(['pre_op_status' => 'not_fit']);

        DB::statement("ALTER TABLE ot_pre_op MODIFY COLUMN pre_op_status ENUM(
            'ready_for_surgery',
            'not_fit'
        ) NULL");
    }
};
