<?php

/**
 * 2025_01_02_000014_create_ot_tables.php
 *
 * PURPOSE: OT (Operation Theatre) Module tables.
 *          Booking → Payment → Ward → Pre-Op → Dilation → Surgery → Lens → Invoice
 *          Status flow: booked → paid → in_ward → dilated → ready → operated → discharged
 *          Sab tables tenant_id ke saath — cross-hospital isolation.
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
        // OT slots master (for surgery scheduling)
        Schema::create('tbl_ot_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('slot_name');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // OT Bookings
        Schema::create('ot_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('ot_doctor_id')->constrained('ot_staff');
            $table->foreignId('booked_by')->constrained('ot_staff');
            $table->date('surgery_date');
            $table->foreignId('slot_id')->constrained('tbl_ot_slots');
            $table->enum('eye', ['RE', 'LE', 'Both']);
            $table->string('ot_type');             // 'Cataract', 'LASIK', 'Other'
            $table->boolean('reports_ok')->default(false);
            // Counselling details
            $table->boolean('has_mediclaim')->default(false);
            $table->string('lens_option')->nullable();
            $table->decimal('package_amount', 10, 2)->nullable();
            $table->enum('payment_mode', ['cash', 'online', 'mediclaim'])->nullable();
            // Status progression
            $table->enum('ot_status', [
                'booked', 'paid', 'in_ward', 'dilated',
                'ready', 'complicated', 'operated', 'discharged', 'cancelled',
            ])->default('booked');
            $table->timestamp('attended_at')->nullable();   // Ward entry time
            $table->timestamp('operated_at')->nullable();   // Surgery done time
            $table->timestamp('discharged_at')->nullable(); // Discharge time
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'surgery_date', 'ot_status']); // Performance
        });

        // OT Payments (recorded by Accountant)
        Schema::create('ot_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('ot_booking_id')->constrained();
            $table->decimal('package_amount', 10, 2);
            $table->boolean('has_mediclaim')->default(false);
            $table->string('receipt_number')->nullable();
            $table->enum('payment_mode', ['cash', 'online', 'mediclaim']);
            $table->foreignId('recorded_by')->constrained('ot_staff');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Pre-Op vitals
        Schema::create('ot_pre_op', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('ot_booking_id')->constrained();
            $table->string('bp')->nullable();             // '120/80'
            $table->decimal('rbs', 5, 1)->nullable();    // mg/dL
            $table->decimal('temperature', 4, 1)->nullable(); // °F
            $table->decimal('hba1c', 4, 1)->nullable();  // % — diabetic ke liye
            $table->enum('pre_op_status', ['ready', 'complicated'])->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('ot_staff');
            $table->timestamps();
        });

        // Dilation drop tracking
        Schema::create('ot_dilation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('ot_booking_id')->constrained();
            $table->datetime('administered_at');
            $table->string('medicine_name');
            $table->unsignedTinyInteger('drops_count')->default(1);
            $table->boolean('is_instilled')->default(false);
            $table->timestamps();
        });

        // Surgery details
        Schema::create('ot_surgeries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('ot_booking_id')->constrained();
            $table->foreignId('operated_by')->constrained('ot_staff');
            $table->string('surgery_name');
            $table->enum('eye_operated', ['RE', 'LE', 'Both']);
            $table->enum('surgery_status', ['operated', 'not_operated'])->default('operated');
            $table->text('complication')->nullable();
            $table->timestamp('surgery_at')->nullable();
            $table->timestamps();
        });

        // Lens (IOL) details
        Schema::create('ot_lens_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('ot_booking_id')->constrained();
            $table->string('lens_name');
            $table->enum('lens_type', ['A', 'B']);
            $table->decimal('lens_power', 5, 2);
            $table->decimal('lens_mrp', 8, 2);   // Used in invoice
            $table->boolean('is_implanted')->default(false);
            $table->foreignId('entered_by')->constrained('ot_staff');
            $table->timestamps();
        });

        // OT Invoice / Discharge summary
        Schema::create('ot_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('ot_booking_id')->constrained();
            $table->json('line_items');             // [{head: 'IOL Charges', amount: 7000}]
            $table->decimal('total_amount', 10, 2);
            $table->boolean('is_finalized')->default(false);
            $table->foreignId('generated_by')->nullable()->constrained('ot_staff');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_invoices');
        Schema::dropIfExists('ot_lens_details');
        Schema::dropIfExists('ot_surgeries');
        Schema::dropIfExists('ot_dilation_entries');
        Schema::dropIfExists('ot_pre_op');
        Schema::dropIfExists('ot_payments');
        Schema::dropIfExists('ot_bookings');
        Schema::dropIfExists('tbl_ot_slots');
    }
};
