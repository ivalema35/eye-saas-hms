<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Live-safe: only adds 'expired' to tenants.status enum. No data rewrite.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('trial','active','grace','inactive','suspended','expired') NOT NULL DEFAULT 'trial'");
    }

    public function down(): void
    {
        DB::table('tenants')->where('status', 'expired')->update(['status' => 'inactive']);
        DB::statement("ALTER TABLE tenants MODIFY COLUMN status ENUM('trial','active','grace','inactive','suspended') NOT NULL DEFAULT 'trial'");
    }
};
