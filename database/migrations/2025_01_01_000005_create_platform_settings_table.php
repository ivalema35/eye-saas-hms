<?php

/**
 * 2025_01_01_000005_create_platform_settings_table.php
 *
 * PURPOSE: Platform configuration key-value store.
 *          Razorpay keys, mail settings, etc. yahan store honge.
 *          Kuch values encrypted save hongi.
 *
 * TENANT-SCOPED: NO (Global platform config)
 * RELATED MODEL: App\Models\Platform\PlatformSetting
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            // Kuch settings encrypted save hongi (Razorpay secret key etc.)
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->string('group')->nullable(); // 'payment', 'email', 'general'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
