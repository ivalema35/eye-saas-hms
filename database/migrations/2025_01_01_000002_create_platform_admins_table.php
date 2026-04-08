<?php

/**
 * 2025_01_01_000002_create_platform_admins_table.php
 *
 * PURPOSE: Super Admin users table.
 *          Platform ke admin aur support staff yahan honge.
 *
 * TENANT-SCOPED: NO (Platform-level table)
 * RELATED MODEL: App\Models\Platform\PlatformAdmin
 * GUARD: superadmin
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'support'])->default('super_admin');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_admins');
    }
};
