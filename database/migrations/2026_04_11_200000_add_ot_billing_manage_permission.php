<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['action' => 'ot.billing.manage'],
            [
                'module' => 'ot',
                'label' => 'Manage OT Billing',
                'description' => 'Manage OT billing settings and billing flow.',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('action', 'ot.billing.manage')
            ->delete();
    }
};
