<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ot_surgeries', function (Blueprint $table) {
            if (! Schema::hasColumn('ot_surgeries', 'complication_status')) {
                $table->string('complication_status')->default('none')->after('eye_operated');
            }

            if (! Schema::hasColumn('ot_surgeries', 'complication_notes')) {
                $table->text('complication_notes')->nullable()->after('complication_status');
            }

            if (! Schema::hasColumn('ot_surgeries', 'ward_medicines')) {
                $table->json('ward_medicines')->nullable()->after('complication_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ot_surgeries', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('ot_surgeries', 'ward_medicines')) {
                $columnsToDrop[] = 'ward_medicines';
            }

            if (Schema::hasColumn('ot_surgeries', 'complication_notes')) {
                $columnsToDrop[] = 'complication_notes';
            }

            if (Schema::hasColumn('ot_surgeries', 'complication_status')) {
                $columnsToDrop[] = 'complication_status';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
