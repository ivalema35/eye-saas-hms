<?php

/**
 * 2025_01_02_000012_create_focs_table.php
 *
 * PURPOSE: Free of Charge (FOC) records.
 *          Doctor case fee maaf karta hai — reception accept karti hai.
 *          accepted = false: reception ne abhi accept nahi kiya.
 *          accepted = true: collection se minus ho gaya.
 *
 * TENANT-SCOPED: YES (tenant_id column)
 * RELATED MODEL: App\Models\Hospital\Foc
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('focs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->foreignId('reception_id')->constrained('receptions'); // Assign kiya kisko
            $table->decimal('foc_fee', 8, 2);
            $table->boolean('accepted')->default(false);
            $table->foreignId('accepted_by')->nullable()->constrained('receptions');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('focs');
    }
};
