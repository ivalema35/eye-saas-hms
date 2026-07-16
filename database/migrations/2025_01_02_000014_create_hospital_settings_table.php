<?php

/**
 * 2025_01_02_000013_create_hospital_settings_table.php
 *
 * PURPOSE: Per-hospital configuration key-value store.
 *          Hospital name, address, logo settings, billing config, etc.
 *          tenant_id + key unique constraint: duplicate keys nahi.
 *
 * TENANT-SCOPED: YES (tenant_id column)
 * RELATED MODEL: App\Models\Hospital\HospitalSetting
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'key']); // Ek tenant me duplicate key nahi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_settings');
    }
};
