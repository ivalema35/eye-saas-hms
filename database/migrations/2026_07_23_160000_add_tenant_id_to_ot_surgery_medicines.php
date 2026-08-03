<?php

/**
 * SaaS isolation: denormalize tenant_id onto ot_surgery_medicines
 * so pivot rows are queryable/scoped without joining ot_surgeries.
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
            return;
        }

        if (! Schema::hasColumn('ot_surgery_medicines', 'tenant_id')) {
            Schema::table('ot_surgery_medicines', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }

        $rows = DB::table('ot_surgery_medicines')
            ->whereNull('tenant_id')
            ->get(['id', 'ot_surgery_id']);

        if ($rows->isEmpty()) {
            return;
        }

        $surgeryTenants = DB::table('ot_surgeries')
            ->whereIn('id', $rows->pluck('ot_surgery_id')->unique()->all())
            ->pluck('tenant_id', 'id');

        foreach ($rows as $row) {
            $tid = $surgeryTenants[$row->ot_surgery_id] ?? null;
            if ($tid) {
                DB::table('ot_surgery_medicines')->where('id', $row->id)->update(['tenant_id' => $tid]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ot_surgery_medicines') && Schema::hasColumn('ot_surgery_medicines', 'tenant_id')) {
            Schema::table('ot_surgery_medicines', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
