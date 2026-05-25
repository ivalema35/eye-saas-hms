<?php

/**
 * 2026_04_24_163241_create_missing_ot_support_tables.php
 *
 * PURPOSE: Create 5 missing OT support tables referenced by controllers.
 *          Idempotent — skips tables that already exist.
 *
 * Tables:
 *   1. ot_types          — OT surgery categories
 *   2. ot_surgery_types  — Specific surgeries under each OT type
 *   3. ot_charge_heads   — Billing charge heads with percentage allocation
 *   4. ot_lens_options   — Available IOL lens options
 *   5. ot_counselling    — Counselling details linked to OT bookings
 *
 * TENANT-SCOPED: YES
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent create: only create tables that do not yet exist
        $this->createTableIfNotExists('ot_types', function () {
            Schema::create('ot_types', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name', 150);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'name']);
            });
        });

        $this->createTableIfNotExists('ot_surgery_types', function () {
            Schema::create('ot_surgery_types', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('ot_type_id')->constrained('ot_types')->onDelete('cascade');
                $table->string('surgery_name', 150);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'ot_type_id', 'surgery_name']);
            });
        });

        $this->createTableIfNotExists('ot_charge_heads', function () {
            Schema::create('ot_charge_heads', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('charge_name', 150);
                $table->decimal('percentage', 5, 2)->default(0);
                $table->boolean('is_active')->default(false);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'charge_name']);
            });
        });

        $this->createTableIfNotExists('ot_lens_options', function () {
            Schema::create('ot_lens_options', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name', 150);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'name']);
            });
        });

        // ot_counselling was the only truly missing table
        $this->createTableIfNotExists('ot_counselling', function () {
            Schema::create('ot_counselling', function ($table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('ot_booking_id')->constrained('ot_bookings')->onDelete('cascade');
                $table->boolean('mediclaim')->default(false);
                $table->string('lens_option', 150)->nullable();
                $table->decimal('package_amount', 10, 2)->nullable();
                $table->string('payment_mode', 50)->nullable();
                $table->boolean('report_ok')->default(false);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('hospital_users');
                $table->timestamps();
                $table->index(['tenant_id', 'ot_booking_id']);
            });
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_counselling');
        // The other 4 tables are managed by their own migrations — do not drop here
    }

    private function createTableIfNotExists(string $tableName, callable $callback): void
    {
        if (! Schema::hasTable($tableName)) {
            $callback();
        }
    }
};
