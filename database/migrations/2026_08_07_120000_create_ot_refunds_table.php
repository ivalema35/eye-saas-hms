<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OT full refunds when patient refuses surgery (surgery_refused).
 * Phase 3 — Account desk records money returned to patient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('ot_booking_id')->constrained('ot_bookings')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_mode', 20)->default('cash'); // cash | online
            $table->string('receipt_number', 100)->nullable();
            $table->string('reason', 500)->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('hospital_users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'refunded_at'], 'ot_refunds_tenant_date_idx');
            $table->index(['tenant_id', 'ot_booking_id'], 'ot_refunds_tenant_booking_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_refunds');
    }
};
