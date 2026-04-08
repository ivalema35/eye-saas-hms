<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'is_super')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_super')->default(false)->after('is_system');
            });
        }

        DB::table('roles')
            ->where('slug', 'hospital_admin')
            ->update(['is_super' => true]);

        if (! Schema::hasColumn('role_permissions', 'is_granted')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->boolean('is_granted')->default(false)->after('permission_id');
            });
        }

        if (Schema::hasColumn('role_permissions', 'access_level')) {
            DB::statement("\n                UPDATE role_permissions\n                SET is_granted = CASE\n                    WHEN access_level IN ('read','full') THEN 1\n                    ELSE 0\n                END\n            ");
        }

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'role_permissions')
            ->where('index_name', 'role_permissions_role_id_is_granted_index')
            ->exists();

        if (! $indexExists) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->index(['role_id', 'is_granted']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('role_permissions', 'is_granted')) {
            $indexExists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'role_permissions')
                ->where('index_name', 'role_permissions_role_id_is_granted_index')
                ->exists();

            Schema::table('role_permissions', function (Blueprint $table) use ($indexExists) {
                if ($indexExists) {
                    $table->dropIndex('role_permissions_role_id_is_granted_index');
                }
                $table->dropColumn('is_granted');
            });
        }

        if (Schema::hasColumn('roles', 'is_super')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_super');
            });
        }
    }
};
