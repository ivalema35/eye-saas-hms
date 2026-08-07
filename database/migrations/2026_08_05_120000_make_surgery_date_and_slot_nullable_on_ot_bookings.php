<?php

/**
 * Recommend Surgery no longer collects date/slot — set later in workflow.
 * Allow null surgery_date + slot_id on ot_bookings.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->date('surgery_date')->nullable()->change();
            $table->unsignedBigInteger('slot_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ot_bookings', function (Blueprint $table) {
            $table->date('surgery_date')->nullable(false)->change();
            $table->unsignedBigInteger('slot_id')->nullable(false)->change();
        });
    }
};
