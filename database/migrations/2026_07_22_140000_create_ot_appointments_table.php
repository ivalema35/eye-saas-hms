<?php

/**
 * 2026_07_22_140000_create_ot_appointments_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 2 (Appointment Module).
 *          Pre-registration record captured BEFORE the patient physically arrives —
 *          separate from `patients` (OPD registration) and `ot_bookings` (confirmed
 *          surgery slot). See docs/OT_WORKFLOW_UPGRADE_PRD.md §2.
 *
 *          Department-level segmentation intentionally NOT added (single-specialty
 *          eye hospital — confirmed with client). SMS/WhatsApp send intentionally
 *          NOT added (vendor/API-key decision deferred — confirmed with client).
 *
 * TENANT-SCOPED: YES
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->enum('appointment_type', ['phone', 'walk_in', 'online', 'referral'])->default('walk_in');
            $table->date('appointment_date');
            $table->time('appointment_time')->nullable();
            $table->foreignId('doctor_id')->nullable()->constrained('hospital_users')->nullOnDelete();

            $table->string('patient_name', 200);
            $table->string('mobile_no', 20);
            $table->string('whatsapp_no', 20)->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->foreignId('location_id')->nullable()->constrained('tbl_master_cities')->nullOnDelete();

            $table->enum('status', ['booked', 'confirmed', 'cancelled', 'completed'])->default('booked');
            $table->text('notes')->nullable();

            // Filled in by Reception check-in once the appointment is converted to an OPD visit.
            $table->foreignId('converted_patient_id')->nullable()->constrained('patients')->nullOnDelete();

            $table->foreignId('created_by')->constrained('hospital_users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'appointment_date', 'status']);
            $table->index(['tenant_id', 'mobile_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_appointments');
    }
};
