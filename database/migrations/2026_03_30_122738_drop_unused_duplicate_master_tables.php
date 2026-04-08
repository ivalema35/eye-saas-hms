<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('tbl_chief_complaints');
        Schema::dropIfExists('tbl_kcos');
        Schema::dropIfExists('tbl_diagnoses');
        Schema::dropIfExists('tbl_advices');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tbl_chief_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('complaint');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_kcos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('kco');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('diagnosis');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('advice');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
