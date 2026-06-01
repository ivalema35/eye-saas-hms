<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If old 'name' column still exists, copy it to 'city'
        if (Schema::hasColumn('tbl_locations', 'name') && Schema::hasColumn('tbl_locations', 'city')) {
            DB::statement("UPDATE tbl_locations SET city = name WHERE (city IS NULL OR city = '') AND (name IS NOT NULL AND name != '')");
        }

        // For any remaining records where city is still null, set a placeholder from id
        if (Schema::hasColumn('tbl_locations', 'city')) {
            DB::statement("UPDATE tbl_locations SET city = CONCAT('Location-', id) WHERE city IS NULL OR city = ''");
        }
    }

    public function down(): void
    {
        // Non-destructive — no rollback needed
    }
};
