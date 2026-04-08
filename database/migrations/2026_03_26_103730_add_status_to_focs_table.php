<?php

/**
 * add_status_to_focs_table.php
 *
 * PURPOSE: Extends FOC workflow with explicit status tracking.
 *          pending → accepted | rejected
 *          Also stores a reason for rejected requests.
 *          The existing 'accepted' boolean is preserved for BC.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('focs', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected'])
                  ->default('pending')
                  ->after('accepted_at');
            $table->text('reason')->nullable()->after('status');        // Why FOC requested
            $table->text('rejected_reason')->nullable()->after('reason'); // Why rejected
        });
    }

    public function down(): void
    {
        Schema::table('focs', function (Blueprint $table) {
            $table->dropColumn(['status', 'reason', 'rejected_reason']);
        });
    }
};
