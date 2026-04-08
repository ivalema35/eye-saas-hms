<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('hospital_users')->nullOnDelete();
            $table->string('exam_type', 30);
            $table->json('medicines');
            $table->timestamp('prescribed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'patient_id', 'exam_type'], 'patient_rx_unique_exam_type');
            $table->index(['tenant_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_prescriptions');
    }
};
