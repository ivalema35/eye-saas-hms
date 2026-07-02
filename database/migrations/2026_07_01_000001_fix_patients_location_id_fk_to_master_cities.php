<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old FK pointing to tbl_locations
        try {
            DB::statement('ALTER TABLE `patients` DROP FOREIGN KEY `patients_location_id_foreign`');
        } catch (Exception $e) {
            // FK may not exist — safe to ignore
        }

        // Add new FK pointing to tbl_master_cities
        try {
            DB::statement('ALTER TABLE `patients` ADD CONSTRAINT `patients_location_id_master_city_foreign` FOREIGN KEY (`location_id`) REFERENCES `tbl_master_cities` (`id`) ON DELETE SET NULL');
        } catch (Exception $e) {
            // May already exist
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE `patients` DROP FOREIGN KEY `patients_location_id_master_city_foreign`');
        } catch (Exception $e) {
            //
        }

        try {
            DB::statement('ALTER TABLE `patients` ADD CONSTRAINT `patients_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `tbl_locations` (`id`)');
        } catch (Exception $e) {
            //
        }
    }
};
