<?php

/**
 * THIS FILE IS INTENTIONALLY LEFT EMPTY.
 *
 * REASON: doctors table has been merged into hospital_users table.
 *         See: 2025_01_02_000002_create_hospital_users_table.php
 *
 * DO NOT DELETE THIS FILE — migration numbering must stay sequential.
 * This file runs but creates nothing.
 */

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Merged into hospital_users — nothing to create here
    }

    public function down(): void
    {
        // Nothing to drop
    }
};
