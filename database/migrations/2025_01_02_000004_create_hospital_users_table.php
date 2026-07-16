<?php

/**
 * 2025_01_02_000002_create_hospital_users_table.php
 *
 * PURPOSE: UNIFIED hospital user table — sabhi hospital staff ek table mein.
 *          hospital_admin, doctor, reception, ot_receptionist,
 *          accountant, ot_doctor, ot_assistant — sab yahan hain.
 *
 * REPLACES: hospital_admins + doctors + receptions + ot_staff (4 tables)
 *
 * WHY UNIFIED:
 *   - DB normalized — ek jagah se sab users manage honge
 *   - Ek hi guard (hospital_user) — login logic simple
 *   - role_id FK roles table se — dynamic role assignment
 *   - tenant_id: hospital isolation guaranteed
 *
 * TENANT-SCOPED: YES (tenant_id column)
 * RELATED MODEL: App\Models\Hospital\HospitalUser
 * GUARD: hospital_user
 *
 * ROLE-SPECIFIC COLUMNS (nullable for non-applicable roles):
 *   doctor_type    — sirf Doctor ke liye (primary/secondary)
 *   foc_permission — sirf Doctor ke liye (FOC de sakta hai ya nahi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_users', function (Blueprint $table) {
            $table->id();

            // ── Tenant isolation ──────────────────────────────────────
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // ── Role assignment (dynamic — Hospital Admin sets this) ──
            // NULL allowed for Hospital Admin (first user, role seeded later)
            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->nullOnDelete();

            // ── Core identity fields ──────────────────────────────────
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('contact', 15)->nullable();

            // ── Account status ────────────────────────────────────────
            $table->enum('status', ['active', 'inactive'])->default('active');

            // ── Doctor-specific fields (nullable for all other roles) ──
            // Only populated when user is assigned a Doctor role
            $table->enum('doctor_type', ['primary', 'secondary'])
                ->nullable()
                ->comment('Only for Doctor role: primary = OPD, secondary = specialist');

            $table->boolean('foc_permission')
                ->default(false)
                ->comment('Only for Doctor role: can issue Free Of Charge');

            // ── Auth fields ───────────────────────────────────────────
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // ── Constraints ───────────────────────────────────────────
            // Email unique per hospital (different hospitals can have same email)
            $table->unique(['tenant_id', 'email']);

            // Index for fast login lookups
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_users');
    }
};
