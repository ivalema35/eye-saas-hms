<?php

/**
 * Add implanted_at to ot_lens_details — used by lens usage reports and OT assistant save.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            if (! Schema::hasColumn('ot_lens_details', 'implanted_at')) {
                $table->timestamp('implanted_at')->nullable()->after('is_implanted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ot_lens_details', function (Blueprint $table) {
            if (Schema::hasColumn('ot_lens_details', 'implanted_at')) {
                $table->dropColumn('implanted_at');
            }
        });
    }
};
