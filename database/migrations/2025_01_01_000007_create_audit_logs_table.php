<?php

/**
 * 2025_01_01_000007_create_audit_logs_table.php
 *
 * PURPOSE: Super Admin actions ka complete audit trail.
 *          Kya kiya (action), kya tha (old_values), kya hua (new_values).
 *
 * TENANT-SCOPED: NO (Platform-level audit log)
 * RELATED MODEL: App\Models\Platform\AuditLog
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('platform_admins');
            $table->foreignId('tenant_id')->nullable()->constrained(); // Null = platform action
            $table->string('action');         // 'hospital.suspended', 'hospital.activated'
            $table->text('description');      // Human-readable description
            $table->string('ip_address', 45)->nullable();
            $table->json('old_values')->nullable(); // Kya tha pehle
            $table->json('new_values')->nullable(); // Kya ho gaya ab
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
