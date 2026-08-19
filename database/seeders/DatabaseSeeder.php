<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters:
     *   1. PermissionsSeeder  — Master permissions (no FK dependencies)
     *   2. PlatformAdminSeeder — SuperAdmin account
     */
    public function run(): void
    {
        $this->call([
            // ── System ────────────────────────────────────────────────
            PermissionsSeeder::class,
            SystemRolesSeeder::class,
            PlatformAdminSeeder::class,

            // ── Clinical masters ──────────────────────────────────────
            ChiefComplaintSeeder::class,
            KcoSeeder::class,
            MasterAdviceSeeder::class,
            MasterDiagnosisSeeder::class,
            GlobalMasterDiagnosisSeeder::class,
            MasterHnoSeeder::class,

            // ── Vision masters ────────────────────────────────────────
            MasterVnSeeder::class,
            MasterVnglSeeder::class,
            MasterVnstSeeder::class,
            MasterPnvnSeeder::class,
            MasterNrvnSeeder::class,
            MasterSphCylSeeder::class,
            MasterAxisSeeder::class,
            MasterNctSeeder::class,

            // ── Posterior segment masters ─────────────────────────────
            MasterDiscSeeder::class,
            MasterFrSeeder::class,

            // ── Anterior segment masters (OE) ─────────────────────────
            MasterSacSeeder::class,
            MasterLidSeeder::class,
            MasterConjSeeder::class,
            MasterCorneaSeeder::class,
            MasterAcSeeder::class,
            MasterIrisSeeder::class,
            MasterPupilSeeder::class,
            MasterLensSeeder::class,
            MasterEmSeeder::class,
            MasterCoverTestSeeder::class,

            // ── Medicine masters ──────────────────────────────────────
            DosageSeeder::class,
            MedicineTypeSeeder::class,
            MedicineCategorySeeder::class,
            MedicineRouteSeeder::class,
            MasterDosageSeeder::class,
            MasterMedicineTypeSeeder::class,
            MasterMedicineCategorySeeder::class,
            MasterMedicineRouteSeeder::class,
        ]);
    }
}
