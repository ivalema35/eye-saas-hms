<?php

/**
 * OT 1.0 Remaining PRD — Phase B1.
 * Proper ot_surgery_medicines pivot (replaces free-form ward_medicines JSON reads).
 * Backfills existing JSON rows where medicine name resolves to medicines.id.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ot_surgery_medicines')) {
            Schema::create('ot_surgery_medicines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ot_surgery_id')->constrained('ot_surgeries')->cascadeOnDelete();
                $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
                $table->string('medicine_name', 255)->nullable(); // denormalized label for prints / unresolved names
                $table->decimal('quantity', 8, 2)->nullable()->default(1);
                $table->string('dose', 255)->nullable();
                $table->timestamps();

                $table->index(['ot_surgery_id']);
            });
        }

        // Backfill from legacy ward_medicines JSON (best-effort).
        $surgeries = DB::table('ot_surgeries')
            ->whereNotNull('ward_medicines')
            ->where('ward_medicines', '!=', '[]')
            ->where('ward_medicines', '!=', 'null')
            ->get(['id', 'tenant_id', 'ward_medicines']);

        $now = now();

        foreach ($surgeries as $surgery) {
            $items = is_string($surgery->ward_medicines)
                ? (json_decode($surgery->ward_medicines, true) ?: [])
                : (array) $surgery->ward_medicines;

            if (! is_array($items) || $items === []) {
                continue;
            }

            $already = DB::table('ot_surgery_medicines')
                ->where('ot_surgery_id', $surgery->id)
                ->exists();

            if ($already) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $name = trim((string) ($item['medicine'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $medicineId = DB::table('medicines')
                    ->where('tenant_id', $surgery->tenant_id)
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($name) {
                        $q->where('name', $name)->orWhere('brand_name', $name);
                    })
                    ->value('id');

                DB::table('ot_surgery_medicines')->insert([
                    'ot_surgery_id' => $surgery->id,
                    'medicine_id' => $medicineId,
                    'medicine_name' => $name,
                    'quantity' => 1,
                    'dose' => isset($item['dose']) ? (string) $item['dose'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_surgery_medicines');
    }
};
