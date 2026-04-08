<?php

/**
 * Make non-admin roles editable (is_system = false).
 *
 * After this: only Hospital Admin has is_system=true.
 * Doctor, Receptionist, and any OT roles become editable/deletable by the admin.
 * OT roles are NOT deleted — admin may have assigned users to them.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mark all non-admin roles as editable (is_system = false)
        DB::table('roles')
            ->where('slug', '!=', 'hospital_admin')
            ->update(['is_system' => false]);
    }

    public function down(): void
    {
        // Restore is_system=true for the original 7 system role slugs
        DB::table('roles')
            ->whereIn('slug', [
                'doctor', 'receptionist', 'ot_receptionist',
                'accountant', 'ot_doctor', 'ot_assistant',
            ])
            ->update(['is_system' => true]);
    }
};
