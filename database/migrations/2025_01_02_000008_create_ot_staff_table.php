<?php

/**
 * 2025_01_02_000007_create_ot_staff_table.php
 *
 * NOTE: ot_staff table has been merged into hospital_users table.
 *       This migration creates a minimal stub table so that other
 *       migrations referencing `ot_staff` via FK constraints can succeed.
 *       The FK is later updated to point to `hospital_users` by
 *       the dedicated FK-update migration (2026_03_25_1600xx series).
 *
 * DO NOT DELETE THIS FILE — migration numbering must stay sequential.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_staff', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_staff');
    }
};
