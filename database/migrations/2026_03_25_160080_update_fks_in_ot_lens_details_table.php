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
            DB::statement('ALTER TABLE `ot_lens_details` DROP FOREIGN KEY `ot_lens_details_entered_by_foreign`');
        } catch (Exception $e) {
            // FK may already be dropped
        }

        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->foreign('entered_by')->references('id')->on('hospital_users');
        });
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE `ot_lens_details` DROP FOREIGN KEY `ot_lens_details_entered_by_foreign`');
        } catch (Exception $e) {
            // FK may already be dropped
        }

        Schema::table('ot_lens_details', function (Blueprint $table) {
            $table->foreign('entered_by')->references('id')->on('ot_staff');
        });
    }
};
