<?php

/**
 * 2026_07_22_160200_create_ot_lens_powers_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 4 (OT Module Enhancements).
 *          Lens Power master, PDF §10: "Power (master and favourite)" — a small
 *          admin-managed quick-pick list (+10.0D … +30.0D) with a favourite flag,
 *          mirroring the same is_favourite pattern used across the eye-exam
 *          Master* dropdown tables (see PRD_MASTER.md §5.2). Deliberately kept
 *          separate from `ot_lens_options` (which already serves a different
 *          purpose — lens name/option picks in Counselling — since day one of
 *          this codebase) to avoid overloading that table's existing meaning.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §4.
 *
 * TENANT-SCOPED: YES
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_lens_powers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->decimal('power', 5, 2);
            $table->boolean('is_favourite')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'power']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_lens_powers');
    }
};
