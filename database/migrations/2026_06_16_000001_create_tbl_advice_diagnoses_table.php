<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_advice_diagnoses', function (Blueprint $table) {
            $table->unsignedBigInteger('advice_id');
            $table->unsignedBigInteger('diagnosis_id');

            $table->primary(['advice_id', 'diagnosis_id']);

            $table->foreign('advice_id')
                ->references('id')
                ->on('tbl_master_advice')
                ->cascadeOnDelete();

            $table->foreign('diagnosis_id')
                ->references('id')
                ->on('tbl_master_diagnosis')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_advice_diagnoses');
    }
};
