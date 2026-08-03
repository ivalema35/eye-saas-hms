<?php

/**
 * 2026_07_22_190000_create_lens_inventory_table.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 7 (Inventory Module). Real stock-tracked
 *          Lens Master (PDF §10) — SKU, batch/serial, cost, supplier, expiry,
 *          available stock. Feeds the lens picker on the OT Assistant's lens
 *          form (Phase 4) so a specific physical unit can be selected and its
 *          stock decremented on implant.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §7.
 *
 *          Deliberately NOT replacing `ot_lens_options` — that table serves the
 *          Counsellor's higher-level "lens category/option" pick (Phase 1),
 *          decided before the actual physical unit is chosen at surgery time.
 *          Medicine inventory intentionally NOT built — out of scope per PDF.
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
        Schema::create('lens_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('lens_code', 100);
            $table->string('manufacturer', 150)->nullable();
            $table->string('lens_name', 200);
            $table->string('type', 100)->nullable();
            $table->decimal('power', 5, 2)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->string('supplier', 150)->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('available_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'lens_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lens_inventory');
    }
};
