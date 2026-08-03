<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OT Package Master — lens_cost + room_category → package/charges autofill
 * on Counsellor form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_package_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('package_name', 150);
            $table->decimal('lens_cost', 10, 2);
            $table->string('room_category', 20); // general | private
            $table->decimal('ot_charges', 10, 2)->default(0);
            $table->decimal('surgeon_charges', 10, 2)->default(0);
            $table->decimal('nursing_charges', 10, 2)->default(0);
            $table->decimal('consumables_charges', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['tenant_id', 'lens_cost', 'room_category'],
                'ot_package_masters_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_package_masters');
    }
};
