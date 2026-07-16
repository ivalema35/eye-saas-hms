<?php

/**
 * 2025_01_02_000004_create_role_permissions_table.php
 *
 * PURPOSE: Role-Permission mapping — Manual checkbox-based assignment.
 *
 * DESIGN DECISION:
 *   - access_level enum (none/read/full) → REMOVED
 *   - is_granted boolean → ADDED
 *
 * WHY:
 *   Permissions table mein ALREADY granular actions hain:
 *     patient.view, patient.create, patient.edit, patient.delete
 *   Har permission ek specific action hai — is_granted = true/false kaafi hai.
 *   Hospital Admin UI mein har permission ke liye ek checkbox hoga.
 *
 * FLOW:
 *   1. Hospital Admin → Settings → Roles → [Role Name]
 *   2. Permission list dikhti hai grouped by module
 *   3. Har permission ke saamne ek checkbox
 *   4. Save → role_permissions table update hota hai
 *   5. Cache flush hota hai
 *
 * TENANT-SCOPED: INDIRECT (role_id → roles.tenant_id)
 * RELATED MODEL: App\Models\Role\RolePermission
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->onDelete('cascade');

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->onDelete('cascade');

            // Simple boolean: is this permission granted to this role?
            // Hospital Admin UI: checkbox ON = true, OFF = false
            $table->boolean('is_granted')->default(false);

            // Audit: who last updated this permission assignment
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // One row per role-permission pair
            $table->unique(['role_id', 'permission_id']);
            $table->index(['role_id', 'is_granted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
