<?php

/**
 * 2026_07_22_160000_create_ot_verifications_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 4 (OT Module Enhancements).
 *          Pre-surgery verification checklist (identity, consent, payment, correct
 *          eye) — 1:1 with a booking, saved together with the surgery record so the
 *          doctor cannot record surgery without confirming all four.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §4.
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
        Schema::create('ot_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('ot_booking_id')->constrained('ot_bookings')->onDelete('cascade');
            $table->boolean('identity_verified')->default(false);
            $table->boolean('consent_verified')->default(false);
            $table->boolean('payment_verified')->default(false);
            $table->boolean('correct_eye_verified')->default(false);
            $table->foreignId('verified_by')->constrained('hospital_users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'ot_booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_verifications');
    }
};
