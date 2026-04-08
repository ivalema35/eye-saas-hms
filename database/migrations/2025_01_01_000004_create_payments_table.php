<?php

/**
 * 2025_01_01_000004_create_payments_table.php
 *
 * PURPOSE: All payment transactions record.
 *          Online (Razorpay) aur offline (cash/cheque) dono.
 *
 * TENANT-SCOPED: NO (Platform-level transaction log)
 * RELATED MODEL: App\Models\Platform\Payment
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->enum('cycle', ['monthly', 'quarterly', 'yearly']);
            // method: online = Razorpay, offline = cash/cheque by SuperAdmin
            $table->enum('method', ['online', 'offline'])->default('online');
            $table->string('gateway')->nullable();            // 'razorpay'
            $table->string('transaction_id')->nullable();     // Razorpay payment ID
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_signature')->nullable(); // Verification ke liye
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('invoice_path')->nullable();       // Generated PDF path
            $table->text('notes')->nullable();                // Offline payment notes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
