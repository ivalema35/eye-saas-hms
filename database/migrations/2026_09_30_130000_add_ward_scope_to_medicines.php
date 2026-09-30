<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_master_medicines', 'is_ward')) {
            Schema::table('tbl_master_medicines', function (Blueprint $table) {
                $table->boolean('is_ward')->default(false)->after('is_active');
            });
        }

        $scope = DB::select("SHOW COLUMNS FROM medicines LIKE 'usage_scope'");
        $scopeType = $scope[0]->Type ?? '';
        if (! str_contains($scopeType, 'ward')) {
            DB::statement("ALTER TABLE medicines MODIFY COLUMN usage_scope ENUM('opd','ot','ward') NOT NULL DEFAULT 'opd'");
        }
    }

    public function down(): void
    {
        DB::table('medicines')->where('usage_scope', 'ward')->update(['usage_scope' => 'opd']);
        DB::statement("ALTER TABLE medicines MODIFY COLUMN usage_scope ENUM('opd','ot') NOT NULL DEFAULT 'opd'");

        if (Schema::hasColumn('tbl_master_medicines', 'is_ward')) {
            Schema::table('tbl_master_medicines', function (Blueprint $table) {
                $table->dropColumn('is_ward');
            });
        }
    }
};
