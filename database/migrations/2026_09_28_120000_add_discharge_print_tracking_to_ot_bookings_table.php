<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['summary_bill_printed_at', 'discharge_printed_at', 'certificate_printed_at'];

    public function up(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            $after = 'discharged_at';
            foreach (self::COLUMNS as $column) {
                if (! Schema::hasColumn('ot_bookings', $column)) {
                    $table->timestamp($column)->nullable()->after($after);
                }
                $after = $column;
            }
        });
    }

    public function down(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('ot_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
