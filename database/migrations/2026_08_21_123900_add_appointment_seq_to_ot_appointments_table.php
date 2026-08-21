<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ot_appointments', function (Blueprint $table) {
            $table->unsignedInteger('appointment_seq')->nullable()->after('tenant_id');
            $table->unique(['tenant_id', 'appointment_seq'], 'ot_appointments_tenant_seq_unique');
        });

        // Backfill: each hospital starts its own APT sequence from 1 (by created order).
        $tenantIds = DB::table('ot_appointments')
            ->select('tenant_id')
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            $rows = DB::table('ot_appointments')
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->get(['id']);

            $seq = 1;
            foreach ($rows as $row) {
                DB::table('ot_appointments')
                    ->where('id', $row->id)
                    ->update(['appointment_seq' => $seq++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('ot_appointments', function (Blueprint $table) {
            $table->dropUnique('ot_appointments_tenant_seq_unique');
            $table->dropColumn('appointment_seq');
        });
    }
};
