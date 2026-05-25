<?php

/**
 * 2025_01_01_000001_create_tenants_table.php
 *
 * PURPOSE: Platform-level tenant registry.
 *          Har registered hospital/clinic ki ek row hogi yahan.
 *          Ye sabse important platform table hai.
 *
 * TENANT-SCOPED: NO (Platform-level table, no tenant_id)
 * RELATED MODEL: App\Models\Platform\Tenant
 * SEEDER: Database\Seeders\TenantSeeder (testing ke liye)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Hospital ka full naam
            $table->string('slug')->unique();             // URL slug: 'aakasheye'
            $table->string('admin_name');                 // Admin ka naam
            $table->string('admin_email')->unique();
            $table->string('admin_phone', 15);
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('logo_path')->nullable();      // Hospital logo path
            // Subscription status
            $table->enum('status', ['trial', 'active', 'grace', 'inactive', 'suspended'])
                ->default('trial');
            $table->timestamp('trial_ends_at')->nullable();        // 14-day trial end
            $table->timestamp('setup_completed_at')->nullable();   // Setup wizard complete
            $table->boolean('is_setup_done')->default(false);      // Wizard skip flag
            $table->timestamps();
            $table->softDeletes(); // Data 30 din retain rehta hai deletion ke baad
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
