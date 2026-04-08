<?php

/**
 * 2025_01_02_000003_create_roles_table.php
 *
 * PURPOSE: Hospital roles — tenant-scoped.
 *          Hospital Admin MANUALLY creates roles.
 *          is_system = true  → delete nahi kar sakte (but CAN edit permissions)
 *          is_super  = true  → is role ke users bypass karte hain permission checks
 *                              (Hospital Admin role ke liye)
 *
 * RULES:
 *   - Koi bhi DEFAULT permissions auto-assign NAHI hongi
 *   - Hospital Admin manually har role ke liye permissions tick karega
 *   - is_super flag sirf Hospital Admin role pe hoga
 *   - Custom roles create, edit, delete kar sakte hain (agar koi user assigned nahi)
 *
 * TENANT-SCOPED: YES
 * RELATED MODEL: App\Models\Role\Role
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->onDelete('cascade');

            $table->string('name');
            $table->string('slug');

            // System role = cannot be deleted (but permissions CAN be edited)
            $table->boolean('is_system')->default(false);

            // Super role = bypasses all permission checks (Hospital Admin only)
            $table->boolean('is_super')->default(false);

            $table->string('description')->nullable();
            $table->string('color', 7)->default('#1B4F72');

            // Who created this role (FK to hospital_users, nullable for seeded roles)
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Slug must be unique within a tenant
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
