<?php

/**
 * 2025_01_02_000009_create_medicine_tables.php
 *
 * PURPOSE: Medicine master tables per hospital.
 *          Medicine types, dosages, medicines, groups — sab tenant-scoped.
 *
 * TENANT-SCOPED: YES (all tables have tenant_id)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Medicine types (Eye Drop, Tablet, Ointment, etc.)
        Schema::create('medicine_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        // Dosages (1 Drop OD, 1 Drop OU, etc.)
        Schema::create('dosages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('dosage');
            $table->timestamps();
        });

        // Medicines master
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_type_id')->constrained('medicine_types');
            $table->string('name');
            $table->string('brand_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Medicine groups (prescriptions group)
        Schema::create('medicine_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');   // 'Post Cataract Standard'
            $table->timestamps();
            $table->softDeletes();
        });

        // Items in each group
        Schema::create('medicine_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_group_id')->constrained('medicine_groups')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines');
            $table->foreignId('dosage_id')->nullable()->constrained('dosages');
            $table->string('frequency')->nullable(); // 'TDS', 'BD', 'OD'
            $table->string('duration')->nullable();  // '7 days', '1 month'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_group_items');
        Schema::dropIfExists('medicine_groups');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('dosages');
        Schema::dropIfExists('medicine_types');
    }
};
