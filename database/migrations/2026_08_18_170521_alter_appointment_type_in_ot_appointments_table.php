<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE ot_appointments MODIFY COLUMN appointment_type ENUM('phone', 'walk_in', 'online', 'referral', 'ot') NOT NULL DEFAULT 'walk_in'");
        DB::table('ot_appointments')->where('appointment_type', 'referral')->update(['appointment_type' => 'ot']);
        DB::statement("ALTER TABLE ot_appointments MODIFY COLUMN appointment_type ENUM('phone', 'walk_in', 'online', 'ot') NOT NULL DEFAULT 'ot'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ot_appointments MODIFY COLUMN appointment_type ENUM('phone', 'walk_in', 'online', 'referral', 'ot') NOT NULL DEFAULT 'walk_in'");
        DB::table('ot_appointments')->where('appointment_type', 'ot')->update(['appointment_type' => 'referral']);
        DB::statement("ALTER TABLE ot_appointments MODIFY COLUMN appointment_type ENUM('phone', 'walk_in', 'online', 'referral') NOT NULL DEFAULT 'walk_in'");
    }
};
