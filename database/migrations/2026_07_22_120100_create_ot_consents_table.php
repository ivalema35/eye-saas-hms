<?php

/**
 * 2026_07_22_120100_create_ot_consents_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 1 (Counsellor Module).
 *          Informed-consent capture — patient/guardian digital signature, witness,
 *          consent date. Recorded by the Counsellor before the booking can move to
 *          Billing. See docs/OT_WORKFLOW_UPGRADE_PRD.md §1.
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
        Schema::create('ot_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('ot_booking_id')->constrained('ot_bookings')->onDelete('cascade');
            $table->boolean('consent_given')->default(false);
            $table->string('patient_signature_path')->nullable();
            $table->string('guardian_signature_path')->nullable();
            $table->string('witness_name', 150)->nullable();
            $table->timestamp('consent_date')->nullable();
            $table->foreignId('created_by')->constrained('hospital_users');
            $table->timestamps();
            $table->index(['tenant_id', 'ot_booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_consents');
    }
};
