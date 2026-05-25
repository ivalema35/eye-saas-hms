<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE `roles` DROP FOREIGN KEY `roles_created_by_foreign`');
        } catch (Exception $e) {
            // FK may already be dropped
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('hospital_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE `roles` DROP FOREIGN KEY `roles_created_by_foreign`');
        } catch (Exception $e) {
            // FK may already be dropped
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('hospital_admins');
        });
    }
};
