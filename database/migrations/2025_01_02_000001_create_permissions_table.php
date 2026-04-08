<?php

/**
 * 2025_01_02_000001_create_permissions_table.php
 *
 * PURPOSE: Platform-level master permission list.
 *          Ye global table hai — tenant-specific keys nahi.
 *          action format: 'module.submodule.action'
 *
 * TENANT-SCOPED: NO (Global permission catalog)
 * RELATED MODEL: App\Models\Role\Permission
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('module');         // 'opd', 'ot', 'master', 'reports', 'settings'
            $table->string('action')->unique(); // 'opd.patient.register'
            $table->string('label');           // 'Register Walk-in Patient'
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
