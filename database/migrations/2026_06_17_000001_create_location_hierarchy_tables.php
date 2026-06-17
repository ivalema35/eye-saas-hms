<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Level 1 — Countries (root, no FK)
        Schema::create('tbl_master_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });

        // Level 2 — States
        Schema::create('tbl_master_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('tbl_master_countries')->cascadeOnDelete();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_id', 'name']);
        });

        // Level 3 — Districts
        Schema::create('tbl_master_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('tbl_master_states')->cascadeOnDelete();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['state_id', 'name']);
        });

        // Level 4 — Cities / Villages (district is optional)
        Schema::create('tbl_master_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('tbl_master_states')->cascadeOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('tbl_master_districts')->nullOnDelete();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['state_id', 'district_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_master_cities');
        Schema::dropIfExists('tbl_master_districts');
        Schema::dropIfExists('tbl_master_states');
        Schema::dropIfExists('tbl_master_countries');
    }
};
