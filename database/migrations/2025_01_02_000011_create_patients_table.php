<?php

/**
 * 2025_01_02_000010_create_patients_table.php
 *
 * PURPOSE: OPD Patients table.
 *          MRD number auto-generate hota hai per hospital.
 *          Appointment tracking, doctor assignment, exam status.
 *          tenant_id + indexes: performance aur isolation dono.
 *
 * TENANT-SCOPED: YES (tenant_id column + indexes)
 * RELATED MODEL: App\Models\Hospital\Patient
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('patient_code', 20)->nullable(); // AEH0001 — per-tenant auto-gen
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->unsignedSmallInteger('age');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('contact_no', 15);
            $table->foreignId('location_id')->nullable()->constrained('tbl_locations');
            $table->date('appointment_date');
            $table->foreignId('slot_id')->nullable()->constrained('tbl_slots');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->foreignId('case_id')->constrained('tbl_cases');
            $table->decimal('case_fee', 8, 2)->default(0);
            $table->foreignId('reception_id')->nullable()->constrained('receptions');
            $table->foreignId('referrer_id')->nullable()->constrained('tbl_referrers');
            $table->enum('type', ['walkin', 'phone'])->default('walkin');
            $table->boolean('is_old_patient')->default(false);
            $table->timestamp('primary_done_at')->nullable();   // Primary exam complete
            $table->timestamp('secondary_done_at')->nullable(); // Secondary exam complete
            $table->timestamps();
            $table->softDeletes();

            // Performance indexes for report filtering
            $table->index(['tenant_id', 'appointment_date']);
            $table->index(['tenant_id', 'doctor_id']);
            $table->index(['tenant_id', 'reception_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
