<?php

/**
 * 2025_01_02_000011_create_examination_tables.php
 *
 * PURPOSE: OPD Eye Examination tables.
 *          Primary (OPD Doctor) aur Secondary (Specialist) exams.
 *          exam_data JSON format — flexible eye exam schema.
 *          Unique constraint: ek patient ka sirf ek primary exam.
 *
 * TENANT-SCOPED: YES (tenant_id column)
 * RELATED MODELS: App\Models\Hospital\PrimaryExamination, SecondaryExamination
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('primary_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->json('exam_data');                     // Complete eye exam JSON
            $table->timestamp('examined_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tenant_id', 'patient_id']); // Ek patient = ek primary exam
        });

        Schema::create('secondary_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('doctor_id')->constrained('doctors');
            $table->json('exam_data');
            $table->timestamp('examined_at')->useCurrent();
            $table->timestamps();
            $table->unique(['tenant_id', 'patient_id']); // Ek patient = ek secondary exam
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secondary_examinations');
        Schema::dropIfExists('primary_examinations');
    }
};
