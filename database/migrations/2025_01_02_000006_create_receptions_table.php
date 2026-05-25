<?php

/**
 * 2025_01_02_000006_create_receptions_table.php
 *
 * NOTE: receptions table has been merged into hospital_users table.
 *       This migration creates a minimal stub table so that other
 *       migrations referencing `receptions` via FK constraints can succeed.
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
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receptions');
    }
};
