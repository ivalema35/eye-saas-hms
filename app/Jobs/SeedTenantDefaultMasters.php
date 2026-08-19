<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SeedTenantDefaultMasters — Queued Job
 *
 * Populates all OPD master reference tables for a newly created tenant
 * so their dropdown fields are not empty on first login.
 *
 * Data sourced from a real production tenant and covers all eye-hospital
 * specific dropdowns: case types, slots, vision values, slit-lamp findings,
 * diagnoses, advice, and more.
 *
 * Uses DB::table() directly to bypass BelongsToTenant global scope, which
 * requires an active HTTP request context unavailable inside queued jobs.
 *
 * Queue: default  |  Retries: 3  |  Timeout: 120 seconds
 */
class SeedTenantDefaultMasters implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(private readonly int $tenantId)
    {
    }

    public function handle(): void
    {
        Log::info("SeedTenantDefaultMasters: Starting for tenant #{$this->tenantId}");

        $methods = [
            'seedCaseTypes',
            'seedSlots',
            'seedOtTypes',
            'seedOtSurgeryTypes',
            'seedLensOptions',
            'seedDurations',
            'seedChiefComplaints',
            'seedKcos',
            'seedVisionMasters',
            'seedSphCyl',
            'seedAxis',
            'seedNct',
            'seedSac',
            'seedLid',
            'seedConj',
            'seedCornea',
            'seedAc',
            'seedIris',
            'seedPupil',
            'seedLens',
            'seedEm',
            'seedCovertest',
            'seedDisc',
            'seedFr',
            'seedAdvice',
            'seedDiagnosis',
            'seedMedicineInstructions',
            'seedOtChargeHeads',
            'seedDosages',
            'seedMedicineTypes',
            'seedMedicineCategories',
            'seedMedicineRoutes',
            'seedMedicines',
            'seedHno',
        ];

        $errors = [];
        foreach ($methods as $method) {
            try {
                $this->$method();
            } catch (\Throwable $e) {
                $errors[] = "{$method}: {$e->getMessage()}";
                Log::error("SeedTenantDefaultMasters [{$method}] failed for tenant #{$this->tenantId}: {$e->getMessage()}");
            }
        }

        if (!empty($errors)) {
            Log::warning("SeedTenantDefaultMasters: Completed with " . count($errors) . " error(s) for tenant #{$this->tenantId}.", $errors);
        } else {
            Log::info("SeedTenantDefaultMasters: Completed successfully for tenant #{$this->tenantId}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(
            "SeedTenantDefaultMasters FAILED for tenant #{$this->tenantId}: "
            . $exception->getMessage() . "\n" . $exception->getTraceAsString()
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function ts(): string
    {
        return now()->toDateTimeString();
    }

    /** Map a flat array of strings to tenant-scoped rows for a given column. */
    private function valueRows(array $values, string $column = 'value'): array
    {
        $ts = $this->ts();

        return array_map(fn($v) => [
            'tenant_id' => $this->tenantId,
            $column => $v,
            'created_at' => $ts,
            'updated_at' => $ts,
        ], $values);
    }

    private function insert(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        DB::table($table)->insert($rows);
    }

    /** Insert only if the table has NO existing rows for this tenant. */
    private function insertIfEmpty(string $table, array $rows): void
    {
        if (DB::table($table)->where('tenant_id', $this->tenantId)->exists()) {
            return;
        }

        $this->insert($table, $rows);
    }

    // ─────────────────────────────────────────────────────────────────
    // OPD Basics
    // ─────────────────────────────────────────────────────────────────

    private function seedCaseTypes(): void
    {
        // If tenant already has case rows (e.g. added during setup), skip inserting defaults.
        $existing = DB::table('tbl_cases')->where('tenant_id', $this->tenantId)->count();
        if ($existing > 0) {
            return;
        }

        $ts = $this->ts();

        $this->insert('tbl_cases', [
            ['tenant_id' => $this->tenantId, 'case_type' => 'General OPD', 'case_fee' => 300, 'created_at' => $ts, 'updated_at' => $ts],
            ['tenant_id' => $this->tenantId, 'case_type' => 'Cataract', 'case_fee' => 500, 'created_at' => $ts, 'updated_at' => $ts],
            ['tenant_id' => $this->tenantId, 'case_type' => 'Glaucoma', 'case_fee' => 500, 'created_at' => $ts, 'updated_at' => $ts],
            ['tenant_id' => $this->tenantId, 'case_type' => 'Retina', 'case_fee' => 600, 'created_at' => $ts, 'updated_at' => $ts],
            ['tenant_id' => $this->tenantId, 'case_type' => 'New Case', 'case_fee' => 500, 'created_at' => $ts, 'updated_at' => $ts],
            ['tenant_id' => $this->tenantId, 'case_type' => 'Old Case', 'case_fee' => 300, 'created_at' => $ts, 'updated_at' => $ts],
        ]);
    }

    private function seedSlots(): void
    {
        $defaults = [
            ['slot_name' => 'Slot 9to12', 'start_time' => '09:00:00', 'end_time' => '12:00:00'],
            ['slot_name' => 'Slot 12to1', 'start_time' => '12:00:00', 'end_time' => '13:00:00'],
            ['slot_name' => 'Slot 1to3', 'start_time' => '13:00:00', 'end_time' => '15:00:00'],
            ['slot_name' => 'Slot 3to4', 'start_time' => '15:00:00', 'end_time' => '16:00:00'],
        ];

        foreach ($defaults as $row) {
            $existing = DB::table('tbl_ot_slots')
                ->where('tenant_id', $this->tenantId)
                ->where('slot_name', $row['slot_name'])
                ->first();

            $payload = [
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'deleted_at' => null,
                'updated_at' => $this->ts(),
            ];

            if ($existing) {
                DB::table('tbl_ot_slots')->where('id', $existing->id)->update($payload);

                continue;
            }

            DB::table('tbl_ot_slots')->insert([
                'tenant_id' => $this->tenantId,
                'slot_name' => $row['slot_name'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'created_at' => $this->ts(),
                'updated_at' => $this->ts(),
            ]);
        }
    }

    private function seedOtTypes(): void
    {
        foreach (['Cataract', 'LASIK', 'Other'] as $name) {
            $existing = DB::table('ot_types')
                ->where('tenant_id', $this->tenantId)
                ->where('name', $name)
                ->first();

            $payload = [
                'deleted_at' => null,
                'updated_at' => $this->ts(),
            ];

            if ($existing) {
                DB::table('ot_types')->where('id', $existing->id)->update($payload);

                continue;
            }

            DB::table('ot_types')->insert([
                'tenant_id' => $this->tenantId,
                'name' => $name,
                'created_at' => $this->ts(),
                'updated_at' => $this->ts(),
            ]);
        }
    }

    private function seedLensOptions(): void
    {
        foreach (['Monofocal', 'Multifocal', 'Toric'] as $name) {
            $existing = DB::table('ot_lens_options')
                ->where('tenant_id', $this->tenantId)
                ->where('name', $name)
                ->first();

            $payload = [
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $this->ts(),
            ];

            if ($existing) {
                DB::table('ot_lens_options')->where('id', $existing->id)->update($payload);

                continue;
            }

            DB::table('ot_lens_options')->insert([
                'tenant_id' => $this->tenantId,
                'name' => $name,
                'is_active' => true,
                'created_at' => $this->ts(),
                'updated_at' => $this->ts(),
            ]);
        }
    }

    private function seedOtSurgeryTypes(): void
    {
        $parentMap = [
            'Cataract' => [
                'Phacoemulsification',
                'SICS (Small Incision Cataract Surgery)',
            ],
            'LASIK' => [
                'Standard LASIK',
                'Custom LASIK',
            ],
            'Other' => [
                'Minor Procedure',
            ],
        ];

        foreach ($parentMap as $parentName => $surgeryNames) {
            $parentId = DB::table('ot_types')
                ->where('tenant_id', $this->tenantId)
                ->where('name', $parentName)
                ->whereNull('deleted_at')
                ->value('id');

            if (!$parentId) {
                Log::warning("SeedTenantDefaultMasters: OT type '{$parentName}' missing for tenant #{$this->tenantId}");

                continue;
            }

            foreach ($surgeryNames as $surgeryName) {
                DB::table('ot_surgery_types')->updateOrInsert(
                    [
                        'tenant_id' => $this->tenantId,
                        'ot_type_id' => $parentId,
                        'surgery_name' => $surgeryName,
                    ],
                    [
                        'deleted_at' => null,
                        'created_at' => $this->ts(),
                        'updated_at' => $this->ts(),
                    ]
                );
            }
        }
    }

    private function seedDurations(): void
    {
        $ts = $this->ts();
        $rows = [];

        foreach (['Days', 'Weeks', 'Months', 'Years'] as $d) {
            $rows[] = ['tenant_id' => $this->tenantId, 'duration' => $d, 'created_at' => $ts, 'updated_at' => $ts];
        }

        $this->insertIfEmpty('tbl_durations', $rows);
    }

    private function seedChiefComplaints(): void
    {
        $this->insertIfEmpty('chief_complaints', $this->valueRows([
            'Black spot',
            'Cataract check up',
            'Diplopia',
            'Discharge',
            'DmVn ( Distance + Near)',
            'DmVn ( Distance)',
            'DmVn ( Near)',
            'Eyestrain',
            'Floater',
            'For Lasik',
            'Headache',
            'Itching / Irritation',
            'Pain in Eye',
            'Redness of Eye',
            'Watering / Discharge',
        ], 'value'));
    }

    private function seedKcos(): void
    {
        $this->insertIfEmpty('kcos', $this->valueRows([
            'Acidity Problem',
            'Arthritis',
            'Asthma',
            'Blood Thinner',
            'Brain Problem',
            'Cancer',
            'Cholesterol',
            'D.M',
            'H.T',
            'Heart Problem',
            'Kidney Problem',
            'Thyroid',
        ], 'value'));
    }

    // ─────────────────────────────────────────────────────────────────
    // Vision Masters
    // ─────────────────────────────────────────────────────────────────

    private function seedVisionMasters(): void
    {
        $dvValues = [
            '6/4',
            '6/4 P',
            '6/5',
            '6/5 P',
            '6/6',
            '6/6 P',
            '6/9',
            '6/9 P',
            '6/12',
            '6/12 P',
            '6/18',
            '6/18 P',
            '6/24',
            '6/24 P',
            '6/36',
            '6/36 P',
            '6/60',
            'CF3M',
            'CF2M',
            'CF1M',
            'CFCF',
            'HM',
            'HM/PL/PR4+',
            'HM/PL/PR inacc.',
            'NOPL',
        ];

        $this->insertIfEmpty('tbl_master_vn', $this->valueRows($dvValues));
        $this->insertIfEmpty('tbl_master_vngl', $this->valueRows($dvValues));
        $this->insertIfEmpty('tbl_master_vnst', $this->valueRows($dvValues));

        // PnVN starts with NI (not improved)
        $this->insertIfEmpty('tbl_master_pnvn', $this->valueRows(array_merge(['NI'], $dvValues)));

        // NrVN uses near-vision notation
        $this->insertIfEmpty('tbl_master_nrvn', $this->valueRows([
            'NP',
            'N6',
            'N8',
            'N10',
            'N12',
            'N18',
            'N24',
            'N36',
            'N6 1FT',
            'N8 1FT',
            'N10 1FT',
            'N12 1FT',
            'N18 1FT',
            'N24 1FT',
        ]));
    }

    // ─────────────────────────────────────────────────────────────────
    // Refraction Masters
    // ─────────────────────────────────────────────────────────────────

    private function seedSphCyl(): void
    {
        if (DB::table('tbl_master_sph_cyl')->where('tenant_id', $this->tenantId)->exists()) {
            return;
        }

        $ts = $this->ts();
        $rows = [];

        for ($i = 0.25; $i <= 10.00; $i += 0.25) {
            $rows[] = [
                'tenant_id' => $this->tenantId,
                'value' => number_format($i, 2),
                'is_seeded' => true,
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
        }

        $this->insert('tbl_master_sph_cyl', $rows);
    }

    private function seedAxis(): void
    {
        if (DB::table('tbl_master_axis')->where('tenant_id', $this->tenantId)->exists()) {
            return;
        }

        $ts = $this->ts();
        $rows = [];

        // 5° to 185° in 5° steps (stored with ° suffix to match model mutator output)
        for ($i = 5; $i <= 185; $i += 5) {
            $rows[] = ['tenant_id' => $this->tenantId, 'value' => $i . '°', 'created_at' => $ts, 'updated_at' => $ts];
        }

        $this->insert('tbl_master_axis', $rows);
    }

    private function seedNct(): void
    {
        if (DB::table('tbl_master_nct')->where('tenant_id', $this->tenantId)->exists()) {
            return;
        }

        $ts = $this->ts();
        $rows = [];

        for ($i = 1; $i <= 60; $i++) {
            $rows[] = ['tenant_id' => $this->tenantId, 'value' => (string) $i, 'created_at' => $ts, 'updated_at' => $ts];
        }

        foreach (['Soft', 'High', 'Low'] as $v) {
            $rows[] = ['tenant_id' => $this->tenantId, 'value' => $v, 'created_at' => $ts, 'updated_at' => $ts];
        }

        $this->insert('tbl_master_nct', $rows);
    }

    // ─────────────────────────────────────────────────────────────────
    // Slit-Lamp / Anterior Segment Masters
    // ─────────────────────────────────────────────────────────────────

    private function seedSac(): void
    {
        $this->insertIfEmpty('tbl_master_sac', $this->valueRows([
            'Acute DC',
            'Block',
            'Block with clear fluid',
            'Block with discharge',
            'Fistula',
            'Partially patent',
            'Patent',
            'Roplas +',
        ]));
    }

    private function seedLid(): void
    {
        $this->insertIfEmpty('tbl_master_lid', $this->valueRows([
            'Blepharitis',
            'Chalazion',
            'Cyst',
            'Dermoid',
            'Dystichiasis',
            'Ectropion',
            'Epiblepharon',
            'Hemangioma',
            'Hordeolum',
            'L/L Stye',
            'Lid Tear',
            'Madarosis',
            'Mole',
            'Poliosis',
            'Ptosis',
            'Stye',
            'Swelling',
            'Tear',
            'Trauma',
            'Trichiasis',
            'U/L Stye',
            'Xanthelasma',
        ]));
    }

    private function seedConj(): void
    {
        $this->insertIfEmpty('tbl_master_conj', $this->valueRows([
            'Allergic conjunctivitis',
            'CCC++',
            'Chemosis',
            'Concretions',
            'Conj. Congestion',
            'Conj. Growth',
            'Conj. suture',
            'Conjunctival cyst',
            'Conjunctivitis (Bac)',
            'Conjunctivitis (Viral)',
            'Cyst',
            'Dermoid',
            'Dry eye',
            'Early n. pterygium',
            'Episcleritis',
            'Follicles',
            'Inflammed pinguecula',
            'Inflammed pterygium',
            'Mole',
            'N. pterygium scar',
            'N+T pterygium',
            'Nevus',
            'Oedema',
            'Papillae',
            'Pinguecula',
            'Pterygium',
            'Recurrent n. pterygium',
            'Recurrent t. pterygium',
            'Redness',
            'Scarring',
            'SCH mild',
            'Symblepharon',
            'T. Pterygium',
            'T. Ptr scar',
            'VKC',
        ]));
    }

    private function seedCornea(): void
    {
        $this->insertIfEmpty('tbl_master_cornea', $this->valueRows([
            'Abscess',
            'Adherent leucoma',
            'Arcus senilis',
            'BSK',
            "Central k' opacity",
            "CL, k' haze",
            'Clear',
            'Congestion',
            'Corneal FB',
            'DEG+++',
            'Degeneration',
            'Descemet folds',
            'DM detachment',
            'Dystrophy',
            'Epithelial defect',
            'EPK',
            'Foreign body',
            "K' vascularization",
            'Keratic precipitates (KPs)',
            'Keratitis',
            'Oedema',
            'Opacity - nonsignificant',
            'Opacity - significant',
            'Perforation',
            'Pig. on endo.',
            'Scar',
            'Shield ulcer',
            'SK',
            'SPE',
            'Spheroidal deg',
            'SPK',
            'Suture opacity',
            'Tear',
            'Tear sealed',
            'Thinning',
            "Total k' opacity",
            'Ulcer',
            'Ulcer - bacterial',
            'Ulcer - fungal',
            'Vascularisation',
        ]));
    }

    private function seedAc(): void
    {
        $this->insertIfEmpty('tbl_master_ac', $this->valueRows([
            'Cells +1',
            'Cells +2',
            'Cells +3',
            'Cells +4',
            'Deep AC',
            'Flare +1',
            'Flare +2',
            'Flare +3',
            'Flare +4',
            'Flat',
            "Fuch's",
            'Hyphema',
            'Hypopyon',
            'Inflammatory membrane',
            'Lens matter',
            'PAS',
            'PEX',
            'PI',
            'Pigment',
            'Pigments',
            'Quiet normal depth',
            'Shallow',
            'Shallow air bubble',
            'Silicon oil',
            'VIT',
            'Vit in AC',
        ]));
    }

    private function seedIris(): void
    {
        $this->insertIfEmpty('tbl_master_iris', $this->valueRows([
            'Adherent leucoma',
            'Atrophy',
            'Coloboma',
            'Cyst',
            'Heterochromia',
            'Iridodialysis',
            'Irregular',
            'Nevus',
            'Nodule',
            'Normal',
            'NVI',
            'PEX iritis',
            'PI',
            'Pigment',
            'Prolapse',
            'P. synechia',
            'Rubeosis',
            'SI',
            'Synechia',
            'YAG PI',
        ]));
    }

    private function seedPupil(): void
    {
        $this->insertIfEmpty('tbl_master_pupil', $this->valueRows([
            'Anterior synechia',
            'Atrophy',
            'Dilated',
            'Fixed',
            'Irregular',
            'Mid dilated',
            'Mid dilated (ATRO)',
            'Miotic',
            'Miotic NR>L',
            'Mydriasis 3mm',
            'Mydriasis 4mm',
            'Mydriasis NR>L',
            'Mydriasis (Full)',
            'Mydriasis eccentric',
            'Mydriasis SR>L',
            'Normal',
            'Optic capture',
            'PEX',
            'Post. synechia',
            'RAPD GR1',
            'RAPD GR2',
            'RAPD GR3',
            'RAPD GR4',
            'A_RRRL',
            'SR>L',
            'SR>OVAL',
            'Synechia',
            'Updrawn pupil',
        ]));
    }

    private function seedLens(): void
    {
        $this->insertIfEmpty('tbl_master_lens', $this->valueRows([
            'Absorbed Cataract',
            'AC/PC IOL',
            'ACIOL',
            'Aphakia',
            'ASC',
            'ASC+PSC',
            'Blue dot cataract',
            'Brown cataract',
            'Clear',
            'CLO',
            'Coloboma',
            'Complicated pseudophakia',
            'Congenital',
            'Cortical cataract',
            'Dense cataract',
            'Dense PSC',
            'Developmental',
            'Dot PSC',
            'Early PSC',
            'Grey Reflex',
            'Hyper mature cataract',
            'Hypermature - Morgagnian',
            'Hypermature - Sclerotic',
            'IMC',
            'IMC (NS++)',
            'IMC + Cortical',
            'IMC + PSC',
            'IMC + PSC + ASC',
            'IOL donesis',
            'Mature cataract',
            'Near mature cataract',
            'PCO',
            'Phacodonesis',
            'Phthisis',
            'Pigments on IOL',
            'Pigments on Lens',
            'PPC',
            'PSC',
            'Pseudophakia',
            'Pseudophakia MF',
            'PXF',
            'Rosette',
            'Subluxated',
            'Subluxated IOL',
            'Traumatic',
            'Uveitic cataract',
            'Weak Zonules',
        ]));
    }

    // ─────────────────────────────────────────────────────────────────
    // Posterior Segment / Motility / Fundus
    // ─────────────────────────────────────────────────────────────────

    private function seedEm(): void
    {
        $this->insertIfEmpty('tbl_master_em', $this->valueRows([
            '3rd nerve palsy',
            '6th nerve palsy',
            'Alt. ESO',
            'Alt. EXO',
            'Esotropia',
            'Exotropia',
            'Full',
            'LR palsy',
            'MR palsy',
            'Nystagmus',
            'Restricted',
            'SR under action',
            'Total ophthalmoplegia',
        ]));
    }

    private function seedCovertest(): void
    {
        $this->insertIfEmpty('tbl_master_covertest', $this->valueRows([
            'Orthophoria',
            'Esophoria',
            'Exophoria',
            'Hyperphoria',
            'Hypophoria',
            'Esotropia',
            'Exotropia',
            'Hypertropia',
            'Hypotropia',
            'Alternating Esotropia',
            'Alternating Exotropia',
        ]));
    }

    private function seedDisc(): void
    {
        $this->insertIfEmpty('tbl_master_disc', $this->valueRows([
            '0.2 CDR',
            '0.3 CDR',
            '0.4 CDR',
            '0.5 CDR',
            '0.6 CDR',
            '0.7 CDR',
            '0.8 CDR',
            '0.9 CDR',
            'Normal',
            'Optic atrophy',
            'Pale NRR',
            'Tilted disc',
        ]));
    }

    private function seedFr(): void
    {
        $this->insertIfEmpty('tbl_master_fr', $this->valueRows([
            'ARMD',
            'CME',
            'CSR',
            'DME',
            'FR dull',
            'Lasered PDR',
            'Macular scar',
            'Mild NPDR',
            'Moderate NPDR',
            'Myopic fundus',
            'Normal',
            'PDR',
            'RP',
            'RPE defect',
            'Severe NPDR',
            'VH',
        ]));
    }

    // ─────────────────────────────────────────────────────────────────
    // Prescription Masters (Advice & Diagnosis)
    // ─────────────────────────────────────────────────────────────────

    private function seedAdvice(): void
    {
        $this->insertIfEmpty('tbl_master_advice', $this->valueRows([
            'Avoid dust and smoke',
            'Cold compresses',
            'Dark goggles',
            "Don't rub eyes",
            'Eye patching',
            'Frequent blinking',
            'Lid hygiene',
            'No head bath',
            'No heavy lifting',
            'No swimming',
            'Protective glasses',
            'Read in good light',
            'Restrict mobile/TV use',
            'Use clean handkerchief',
        ], 'value'));
    }

    /**
     * Sourced LIVE from the Super Admin global Diagnosis Master
     * (tbl_global_master_diagnosis) instead of a hardcoded snapshot, so a
     * new tenant always gets whatever currently exists there — same
     * approach as seedMedicineTypes()/seedDosages() below.
     */
    private function seedDiagnosis(): void
    {
        $values = DB::table('tbl_global_master_diagnosis')->where('is_active', true)->pluck('value')->all();
        $this->insertIfEmpty('tbl_master_diagnosis', $this->valueRows($values, 'value'));
    }

    private function seedMedicineInstructions(): void
    {
        $this->insertIfEmpty('tbl_master_medicine_instructions', $this->valueRows([
            'After meals',
            'Before meals',
            'With food',
            'Empty stomach',
            'At bedtime',
            'Shake well before use',
        ]));
    }

    // ─────────────────────────────────────────────────────────────────
    // Medicine Masters
    // ─────────────────────────────────────────────────────────────────

    /**
     * Dosage/Type/Category/Route/Medicine are sourced LIVE from the Super
     * Admin global Medicine Master (tbl_master_*) instead of a hardcoded
     * snapshot, so a new tenant always gets whatever currently exists there.
     */
    private function seedDosages(): void
    {
        $values = DB::table('tbl_master_dosages')->where('is_active', true)->pluck('dosage')->all();
        $this->insertIfEmpty('dosages', $this->valueRows($values, 'dosage'));
    }

    private function seedMedicineTypes(): void
    {
        $values = DB::table('tbl_master_medicine_types')->where('is_active', true)->pluck('name')->all();
        $this->insertIfEmpty('medicine_types', $this->valueRows($values, 'name'));
    }

    private function seedMedicineCategories(): void
    {
        $values = DB::table('tbl_master_medicine_categories')->where('is_active', true)->pluck('name')->all();
        $this->insertIfEmpty('medicine_categories', $this->valueRows($values, 'name'));
    }

    private function seedMedicineRoutes(): void
    {
        $values = DB::table('tbl_master_medicine_routes')->where('is_active', true)->pluck('name')->all();
        $this->insertIfEmpty('medicine_routes', $this->valueRows($values, 'name'));
    }

    private function seedMedicines(): void
    {
        if (DB::table('medicines')->where('tenant_id', $this->tenantId)->exists()) {
            return;
        }

        $masterMedicines = DB::table('tbl_master_medicines')->where('is_active', true)->get();
        if ($masterMedicines->isEmpty()) {
            return;
        }

        $typeNames = DB::table('tbl_master_medicine_types')->pluck('name', 'id');
        $dosageValues = DB::table('tbl_master_dosages')->pluck('dosage', 'id');

        $ts = $this->ts();
        $rows = [];

        foreach ($masterMedicines as $m) {
            $typeName = $typeNames[$m->master_medicine_type_id] ?? null;
            $typeId = $typeName
                ? DB::table('medicine_types')->where('tenant_id', $this->tenantId)
                    ->whereRaw('LOWER(name) = ?', [strtolower($typeName)])->value('id')
                : null;

            if (!$typeId) {
                // medicine_type_id is NOT NULL on the tenant `medicines` table
                continue;
            }

            $dosageValue = $dosageValues[$m->master_dosage_id] ?? null;
            $dosageId = $dosageValue
                ? DB::table('dosages')->where('tenant_id', $this->tenantId)
                    ->whereRaw('LOWER(dosage) = ?', [strtolower($dosageValue)])->value('id')
                : null;

            $rows[] = [
                'tenant_id' => $this->tenantId,
                'medicine_type_id' => $typeId,
                'dosage_id' => $dosageId,
                'name' => $m->name,
                'duration' => $m->duration,
                'qty' => $m->qty,
                'composition' => $m->composition,
                'company' => $m->company,
                'price' => $m->price,
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
        }

        $this->insert('medicines', $rows);
    }

    private function seedHno(): void
    {
        $this->insertIfEmpty('tbl_master_hno', $this->valueRows([
            'Diabetes Mellitus (DM)',
            'Hypertension (HTN)',
            'Asthma',
            'COPD',
            'Thyroid Disorder',
            'Cardiac Disease',
            'Kidney Disease (CKD)',
            'Liver Disease',
            'Stroke (CVA)',
            'Epilepsy',
            'Tuberculosis (TB)',
            'Hepatitis',
            'HIV',
            'Bleeding Disorder',
            'Anemia',
        ]));
    }

    private function seedOtChargeHeads(): void
    {
        $defaults = [
            ['charge_name' => 'Surgeon Fee', 'percentage' => 50.00],
            ['charge_name' => 'Anesthesia', 'percentage' => 15.00],
            ['charge_name' => 'OT Charges', 'percentage' => 20.00],
            ['charge_name' => 'Nursing and Staff', 'percentage' => 10.00],
            ['charge_name' => 'Consumables', 'percentage' => 5.00],
        ];

        foreach ($defaults as $row) {
            $existing = DB::table('ot_charge_heads')
                ->where('tenant_id', $this->tenantId)
                ->where('charge_name', $row['charge_name'])
                ->first();

            $payload = [
                'percentage' => $row['percentage'],
                'is_active' => true,
                'deleted_at' => null,
                'updated_at' => $this->ts(),
            ];

            if ($existing) {
                DB::table('ot_charge_heads')->where('id', $existing->id)->update($payload);

                continue;
            }

            DB::table('ot_charge_heads')->insert([
                'tenant_id' => $this->tenantId,
                'charge_name' => $row['charge_name'],
                'percentage' => $row['percentage'],
                'is_active' => true,
                'created_at' => $this->ts(),
                'updated_at' => $this->ts(),
            ]);
        }
    }
}
