<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $scope = DB::select("SHOW COLUMNS FROM medicine_groups LIKE 'usage_scope'");
        $scopeType = $scope[0]->Type ?? '';
        if (! str_contains($scopeType, 'ward')) {
            DB::statement("ALTER TABLE medicine_groups MODIFY COLUMN usage_scope ENUM('opd','ot','both','ward') NOT NULL DEFAULT 'opd'");
        }

        if (! Schema::hasColumn('medicine_groups', 'platform_ward_group_id')) {
            Schema::table('medicine_groups', function (Blueprint $table) {
                $table->unsignedBigInteger('platform_ward_group_id')->nullable()->after('usage_scope');
                $table->index(['tenant_id', 'platform_ward_group_id'], 'medicine_groups_tenant_platform_ward_idx');
            });
        }

        if (! Schema::hasTable('tbl_master_ward_medicine_groups')) {
            Schema::create('tbl_master_ward_medicine_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('group_code', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // First attempt created this table without foreign keys (MySQL name-length limit).
        Schema::dropIfExists('tbl_master_ward_medicine_group_items');

        Schema::create('tbl_master_ward_medicine_group_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('master_ward_medicine_group_id');
            $table->unsignedBigInteger('master_medicine_id')->nullable();
            $table->unsignedBigInteger('master_dosage_id')->nullable();
            $table->unsignedBigInteger('master_medicine_route_id')->nullable();
            $table->string('duration', 100)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('master_ward_medicine_group_id', 'mwgi_group_fk')
                ->references('id')->on('tbl_master_ward_medicine_groups')->cascadeOnDelete();
            $table->foreign('master_medicine_id', 'mwgi_med_fk')
                ->references('id')->on('tbl_master_medicines')->nullOnDelete();
            $table->foreign('master_dosage_id', 'mwgi_dosage_fk')
                ->references('id')->on('tbl_master_dosages')->nullOnDelete();
            $table->foreign('master_medicine_route_id', 'mwgi_route_fk')
                ->references('id')->on('tbl_master_medicine_routes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_master_ward_medicine_group_items');
        Schema::dropIfExists('tbl_master_ward_medicine_groups');

        Schema::table('medicine_groups', function (Blueprint $table) {
            $table->dropIndex('medicine_groups_tenant_platform_ward_idx');
            $table->dropColumn('platform_ward_group_id');
        });

        DB::table('medicine_groups')->where('usage_scope', 'ward')->update(['usage_scope' => 'both']);
        DB::statement("ALTER TABLE medicine_groups MODIFY COLUMN usage_scope ENUM('opd','ot','both') NOT NULL DEFAULT 'opd'");
    }
};
