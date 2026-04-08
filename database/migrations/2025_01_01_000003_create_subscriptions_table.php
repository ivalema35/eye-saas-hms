<?php

/**
 * 2025_01_01_000003_create_subscriptions_table.php
 *
 * PURPOSE: Active subscriptions per tenant.
 *          Billing cycle, amount, start/end dates track karta hai.
 *
 * TENANT-SCOPED: NO (References tenant_id via FK but is platform table)
 * RELATED MODEL: App\Models\Platform\Subscription
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            // Billing cycle: 30/90/365 days
            $table->enum('cycle', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('price', 10, 2);           // Actual amount paid
            $table->decimal('original_price', 10, 2);  // Before any discount
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('grace_ends_at')->nullable(); // Expiry + 7 days
            $table->string('razorpay_sub_id')->nullable();
            $table->string('razorpay_plan_id')->nullable();
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
