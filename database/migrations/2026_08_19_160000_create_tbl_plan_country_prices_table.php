<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tbl_plan_country_prices
 *
 * Stores per-country price overrides for each billing cycle.
 * If a country row exists, that exact price is shown during registration
 * instead of the fx_inr_per_unit auto-conversion.
 *
 * Safe to run on live — new table only, no existing tables altered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_plan_country_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id');
            $table->enum('cycle', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('country_id')
                  ->references('id')
                  ->on('tbl_master_countries')
                  ->onDelete('cascade');

            $table->unique(['country_id', 'cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_plan_country_prices');
    }
};
