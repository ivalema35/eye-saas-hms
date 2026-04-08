<?php

/**
 * create_exam_prescriptions_table.php
 *
 * PURPOSE: Medicine prescriptions linked to a primary examination.
 *          Each row = one medicine line in the prescription slip.
 *          Supports RE/LE/Both/OU eye targeting.
 *
 * TENANT-SCOPED: YES (tenant_id column)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('primary_examination_id')->constrained('primary_examinations')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines');
            $table->foreignId('dosage_id')->nullable()->constrained('dosages')->nullOnDelete();
            $table->string('frequency')->nullable();   // TDS, BD, OD, HS, PRN
            $table->string('duration')->nullable();    // 7 days, 1 month
            $table->enum('eye', ['RE', 'LE', 'Both', 'OU'])->default('Both');
            $table->text('instructions')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'primary_examination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_prescriptions');
    }
};
