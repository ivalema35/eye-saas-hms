<?php

/**
 * 2025_01_02_000002_create_hospital_admins_table.php
 *
 * PURPOSE: Hospital admin user accounts.
 *          Har hospital ka admin is table me hoga.
 *          tenant_id: data isolation ke liye zaroori.
 *
 * TENANT-SCOPED: YES (tenant_id column present)
 * RELATED MODEL: App\Models\Hospital\HospitalAdmin
 * GUARD: hospital_admin
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('contact', 15)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['tenant_id', 'email']); // Ek tenant me unique email
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_admins');
    }
};
