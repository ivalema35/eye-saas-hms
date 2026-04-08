<?php

/**
 * 2025_01_01_000006_create_platform_notifications_table.php
 *
 * PURPOSE: Email sending log for platform notifications.
 *          Subscription expiry, welcome emails, invoices ka record.
 *
 * TENANT-SCOPED: NO (Platform-level log)
 * RELATED MODEL: App\Models\Platform\PlatformNotification
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained();
            $table->string('type');             // 'subscription_expiry', 'welcome', 'invoice'
            $table->string('subject');
            $table->string('recipient_email');
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable(); // Fail hone pe reason
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
    }
};
