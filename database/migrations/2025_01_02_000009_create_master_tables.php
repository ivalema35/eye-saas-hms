<?php

/**
 * 2025_01_02_000008_create_master_tables.php
 *
 * PURPOSE: All OPD master reference tables.
 *          Cases, locations, slots, referrers, durations, complaints, etc.
 *          Sab me tenant_id hai — hospital isolation guaranteed.
 *
 * TENANT-SCOPED: YES (all tables have tenant_id)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Case types (New Patient, Old Patient, etc.)
        Schema::create('tbl_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('case_type');
            $table->decimal('case_fee', 8, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // City/Village locations
        Schema::create('tbl_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        // OPD Appointment slots
        Schema::create('tbl_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('slot_name');     // 'Morning', '9:00 AM - 10:00 AM'
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Patient referrers (doctors who referred)
        Schema::create('tbl_referrers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('contact', 15)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Appointment durations
        Schema::create('tbl_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('duration');   // '1 Week', '2 Weeks', '1 Month'
            $table->timestamps();
        });

        // Chief complaints
        Schema::create('tbl_chief_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('complaint');
            $table->timestamps();
            $table->softDeletes();
        });

        // KCO (Known Condition Of) master
        Schema::create('tbl_kcos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('kco');
            $table->timestamps();
            $table->softDeletes();
        });

        // Diagnoses master
        Schema::create('tbl_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('diagnosis');
            $table->timestamps();
            $table->softDeletes();
        });

        // Advice master
        Schema::create('tbl_advices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('advice');
            $table->timestamps();
            $table->softDeletes();
        });

        // Vision masters (VN, PnVn, NrVN)
        Schema::create('tbl_master_vn', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_pnvn', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_nrvn', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        // SPH / CYL values
        Schema::create('tbl_master_sph_cyl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        // AXIS values
        Schema::create('tbl_master_axis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        // NCT (Non-Contact Tonometry)
        Schema::create('tbl_master_nct', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        // DISC examination
        Schema::create('tbl_master_disc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        // Frame (FR) master
        Schema::create('tbl_master_fr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        // Ocular Examination (OE) masters
        Schema::create('tbl_master_sac', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_lid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_conj', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_cornea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_ac', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_iris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_pupil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_lens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_em', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('tbl_master_covertest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tables = [
            'tbl_master_covertest', 'tbl_master_em', 'tbl_master_lens',
            'tbl_master_pupil', 'tbl_master_iris', 'tbl_master_ac',
            'tbl_master_cornea', 'tbl_master_conj', 'tbl_master_lid',
            'tbl_master_sac', 'tbl_master_fr', 'tbl_master_disc',
            'tbl_master_nct', 'tbl_master_axis', 'tbl_master_sph_cyl',
            'tbl_master_nrvn', 'tbl_master_pnvn', 'tbl_master_vn',
            'tbl_advices', 'tbl_diagnoses', 'tbl_kcos', 'tbl_chief_complaints',
            'tbl_durations', 'tbl_referrers', 'tbl_slots',
            'tbl_locations', 'tbl_cases',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
