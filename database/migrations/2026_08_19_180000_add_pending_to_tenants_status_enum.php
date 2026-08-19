<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Live-safe: adds 'pending' (registration awaiting SuperAdmin approval).
 * Includes existing statuses plus expired if that migration already ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('trial','active','grace','inactive','suspended','expired','pending') NOT NULL DEFAULT 'trial'");
    }

    public function down(): void
    {
        DB::table('tenants')->where('status', 'pending')->update(['status' => 'inactive']);
        DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('trial','active','grace','inactive','suspended','expired') NOT NULL DEFAULT 'trial'");
    }
};
