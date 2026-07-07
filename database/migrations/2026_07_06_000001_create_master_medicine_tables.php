<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_master_dosages', function (Blueprint $table) {
            $table->id();
            $table->string('dosage', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('dosage');
        });

        Schema::create('tbl_master_medicine_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('tbl_master_medicine_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('tbl_master_medicine_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('tbl_master_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_medicine_type_id')->nullable()->constrained('tbl_master_medicine_types')->nullOnDelete();
            $table->foreignId('master_dosage_id')->nullable()->constrained('tbl_master_dosages')->nullOnDelete();
            $table->string('name', 255);
            $table->string('duration', 100)->nullable();
            $table->string('qty', 50)->nullable();
            $table->text('composition')->nullable();
            $table->string('company', 255)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_master_medicines');
        Schema::dropIfExists('tbl_master_medicine_routes');
        Schema::dropIfExists('tbl_master_medicine_categories');
        Schema::dropIfExists('tbl_master_medicine_types');
        Schema::dropIfExists('tbl_master_dosages');
    }
};
