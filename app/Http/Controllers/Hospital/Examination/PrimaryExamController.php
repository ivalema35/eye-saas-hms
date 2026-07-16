<?php

namespace App\Http\Controllers\Hospital\Examination;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\Examination\StorePrimaryExamRequest;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineGroup;
use App\Models\Hospital\MedicineRoute;
use App\Models\Hospital\MasterAdvice;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use App\Services\Hospital\ExaminationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Primary Eye Examination Controller
 *
 * Handles the full 10-section primary OPD eye examination.
 * Sections: A-Complaint, B-KCO, C-Vision, D-PG, E-ST,
 *           F-NCT, G-OE, H-Fundus, I-Diagnosis, J-Advice+Rx
 *
 * URL: /{slug}/exam/primary/{id}
 */
class PrimaryExamController extends Controller
{
    public function __construct(private ExaminationService $examinationService) {}

    public function show(string $slug, int $id): View
    {
        $patient = Patient::findOrFail($id);
        $exam = PrimaryExamination::where('patient_id', $id)
            ->with('prescriptions.medicine', 'prescriptions.dosage')
            ->first();
        $tenant = app('tenant');
        $user = Auth::guard('hospital_user')->user();

        $doctors = HospitalUser::query()
            ->where('tenant_id', $tenant->id)
            ->active()
            ->where(function ($query) {
                $query->whereNotNull('doctor_type')
                    ->orWhereHas('role', function ($q) {
                        $q->where(function ($inner) {
                            $inner->whereIn('slug', ['doctor', 'ot_doctor'])
                                ->orWhereIn('name', ['doctor', 'ot_doctor']);
                        });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);
        $focReceptionists = HospitalUser::whereHas('role', fn ($q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->active()->orderBy('name')->get(['id', 'name']);

        $currentDoctorId = in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true)
            ? $user?->id
            : ($patient->doctor_id ?: $user?->id);

        $masters = $this->loadMasters($tenant->id);
        $masters['doctors'] = $doctors;

        return view('hospital.exam.primary', compact(
            'patient', 'exam', 'slug', 'doctors',
            'currentDoctorId', 'masters', 'focReceptionists'
        ));
    }

    public function save(StorePrimaryExamRequest $request, string $slug, int $id): RedirectResponse
    {
        $patient = Patient::findOrFail($id);
        $tenant = app('tenant');
        $data = $request->validated();
        unset($data['exam_data']['advices']);
        $medicines = collect($data['medicines'] ?? [])
            ->filter(fn (array $m): bool => ! empty($m['name']))
            ->map(function (array $medicine) use ($tenant): array {
                if (! empty($medicine['medicine_id']) || empty($medicine['name'])) {
                    return $medicine;
                }

                $submittedName = trim($medicine['name']);

                $foundMedicine = Medicine::query()
                    ->where('tenant_id', $tenant->id)
                    ->where(function ($query) use ($submittedName) {
                        $query->where('brand_name', $submittedName)
                            ->orWhere('name', $submittedName);
                    })
                    ->first(['id']);

                $medicine['medicine_id'] = $foundMedicine?->id;

                return $medicine;
            })
            ->all();

        $this->examinationService->savePrimary(
            $patient,
            (int) $data['doctor_id'],
            $data['exam_data'] ?? [],
            $medicines,
            $tenant->id,
            ! empty($data['dilation_time']) ? (int) $data['dilation_time'] : null
        );

        $user = Auth::guard('hospital_user')->user();

        if (in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true)) {
            return redirect()
                ->route('hospital.exam.primary.print', ['slug' => $slug, 'id' => $id])
                ->with('success', 'Primary examination saved successfully.');
        }

        return redirect()
            ->route('hospital.exam.primary.show', ['slug' => $slug, 'id' => $id])
            ->with('success', 'Primary examination saved successfully.');
    }

    /**
     * Print prescription view.
     */
    public function printRx(string $slug, int $id): View
    {
        $patient = Patient::findOrFail($id);
        $exam = PrimaryExamination::where('patient_id', $id)
            ->with('prescriptions.medicine.medicineType', 'prescriptions.dosage', 'doctor')
            ->firstOrFail();
        $tenant = app('tenant');

        $primaryDoctorName = $exam->doctor?->name;
        $secondaryDoctorName = SecondaryExamination::where('patient_id', $id)
            ->with('doctor')
            ->first()?->doctor?->name;

        $diagnosisMasters = collect(DB::table('tbl_master_diagnosis')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as diagnosis')]));

        $adviceMasters = collect(DB::table('tbl_master_advice')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as advice')]));

        $complaintMasters = collect(DB::table('chief_complaints')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as complaint')]));

        $kcoMasters = collect(DB::table('kcos')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get(['id', DB::raw('value as kco')]));

        $user = Auth::guard('hospital_user')->user();
        $backUrl = in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true)
            ? route('hospital.dashboard', ['slug' => $slug])
            : 'javascript:history.back()';

        return view('hospital.exam.print', compact(
            'patient', 'exam', 'tenant', 'slug',
            'diagnosisMasters', 'adviceMasters', 'complaintMasters', 'kcoMasters', 'backUrl',
            'primaryDoctorName', 'secondaryDoctorName'
        ));
    }

    /**
     * Dense single-page Clinical HUD view.
     */
    public function compactView(string $slug, int $id): View
    {
        $patient = Patient::findOrFail($id);
        $exam = PrimaryExamination::where('patient_id', $id)
            ->with('prescriptions.medicine.medicineType', 'prescriptions.dosage', 'doctor')
            ->firstOrFail();
        $tenant = app('tenant');

        $diagnosisMasters = collect(DB::table('tbl_master_diagnosis')
            ->where('tenant_id', $tenant->id)->orderBy('id')
            ->get(['id', DB::raw('value as diagnosis')]));

        $adviceMasters = collect(DB::table('tbl_master_advice')
            ->where('tenant_id', $tenant->id)->orderBy('id')
            ->get(['id', DB::raw('value as advice')]));

        $complaintMasters = collect(DB::table('chief_complaints')
            ->where('tenant_id', $tenant->id)->orderBy('id')
            ->get(['id', DB::raw('value as complaint')]));

        $kcoMasters = collect(DB::table('kcos')
            ->where('tenant_id', $tenant->id)->orderBy('id')
            ->get(['id', DB::raw('value as kco')]));

        return view('hospital.exam.compact_view', compact(
            'patient', 'exam', 'tenant', 'slug',
            'diagnosisMasters', 'adviceMasters', 'complaintMasters', 'kcoMasters'
        ));
    }

    /**
     * AJAX: Add a new chief complaint to the master table.
     */
    public function ajaxAddComplaint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'complaint' => ['required', 'string', 'max:255'],
        ]);

        $tenantId = app('tenant')->id;
        $id = DB::table('chief_complaints')->insertGetId([
            'tenant_id' => $tenantId,
            'value' => trim($validated['complaint']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id, 'complaint' => trim($validated['complaint'])]);
    }

    /**
     * AJAX: Add a new diagnosis to the master table.
     */
    public function ajaxAddDiagnosis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'diagnosis' => ['required', 'string', 'max:255'],
        ]);

        $tenantId = app('tenant')->id;
        $id = DB::table('tbl_master_diagnosis')->insertGetId([
            'tenant_id' => $tenantId,
            'value' => trim($validated['diagnosis']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $id, 'diagnosis' => trim($validated['diagnosis'])]);
    }

    /**
     * AJAX: Add a new advice to the master table.
     */
    public function ajaxAddAdvice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'advice' => ['required', 'string', 'max:255'],
        ]);

        $tenantId = app('tenant')->id;
        $advice = MasterAdvice::create([
            'tenant_id'    => $tenantId,
            'value'        => trim($validated['advice']),
            'is_favourite' => false,
        ]);

        return response()->json(['id' => $advice->id, 'advice' => $advice->value]);
    }

    /**
     * AJAX: Search medicines for prescription type-ahead.
     */
    public function ajaxSearchMedicines(Request $request): JsonResponse
    {
        $term = $request->input('q', '');

        $medicines = Medicine::where('name', 'like', "%{$term}%")
            ->orWhere('brand_name', 'like', "%{$term}%")
            ->with('medicineType:id,name')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'brand_name', 'medicine_type_id']);

        return response()->json($medicines);
    }

    /**
     * Load all master dropdown data for the exam form.
     *
     * @return array<string, mixed>
     */
    private function loadMasters(int $tenantId): array
    {
        $q = fn (string $table) => DB::table($table)
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get();

        return [
            'complaints' => DB::table('chief_complaints')
                ->where('tenant_id', $tenantId)
                ->orderByDesc('is_favourite')
                ->orderBy('value')
                ->get(['id', DB::raw('value as complaint'), 'is_favourite']),
            'kcos' => DB::table('kcos')
                ->where('tenant_id', $tenantId)
                ->orderByDesc('is_favourite')
                ->orderBy('value')
                ->get(['id', DB::raw('value as kco'), 'is_favourite']),
            'hnos' => DB::table('tbl_master_hno')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->orderByDesc('is_favourite')
                ->orderBy('value')
                ->get(['id', DB::raw('value as hno'), 'is_favourite']),
            'diagnoses' => DB::table('tbl_master_diagnosis')
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->get(['id', DB::raw('value as diagnosis')]),
            'advices' => MasterAdvice::where('tenant_id', $tenantId)
                ->with('diagnoses:id')
                ->orderByDesc('is_favourite')
                ->orderBy('id')
                ->get(['id', 'value', 'is_favourite'])
                ->map(fn($a) => (object)[
                    'id'            => $a->id,
                    'advice'        => $a->value,
                    'is_favourite'  => $a->is_favourite,
                    'diagnosis_ids' => $a->diagnoses->pluck('id')->all(),
                ]),
            'durations' => $q('tbl_durations'),
            'vn' => $q('tbl_master_vn'),
            'pnvn' => $q('tbl_master_pnvn'),
            'nrvn' => $q('tbl_master_nrvn'),
            'sph_cyl' => DB::table('tbl_master_sph_cyl')->where('tenant_id', $tenantId)->whereNull('deleted_at')->orderBy('id')->get(),
            'axis' => $q('tbl_master_axis'),
            'fr' => DB::table('tbl_master_fr')
                ->where('tenant_id', $tenantId)
                ->orderByDesc('is_favourite')
                ->orderBy('value')
                ->get(['id', 'value', 'is_favourite']),
            'nct' => $q('tbl_master_nct'),
            'disc' => DB::table('tbl_master_disc')
                ->where('tenant_id', $tenantId)
                ->orderByDesc('is_favourite')
                ->orderBy('value')
                ->get(['id', 'value', 'is_favourite']),
            'sac' => $q('tbl_master_sac'),
            'lid' => $q('tbl_master_lid'),
            'conj' => $q('tbl_master_conj'),
            'cornea' => $q('tbl_master_cornea'),
            'ac' => $q('tbl_master_ac'),
            'iris' => $q('tbl_master_iris'),
            'pupil' => $q('tbl_master_pupil'),
            'lens_master' => $q('tbl_master_lens'),
            'em' => $q('tbl_master_em'),
            'covertest' => $q('tbl_master_covertest'),
            'dosages' => Dosage::orderBy('dosage')->get(['id', 'dosage']),
            'instructions' => $q('tbl_master_medicine_instructions'),
            'medicines' => Medicine::query()
                ->where('tenant_id', $tenantId)
                ->with('dosage:id,dosage')
                ->orderBy('brand_name')
                ->orderBy('name')
                ->get(['id', 'name', 'brand_name', 'dosage_id', 'duration', 'qty']),
            'med_groups' => MedicineGroup::with('items.medicine', 'items.dosage', 'items.route')
                ->where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(),
            'routes' => MedicineRoute::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'referrers' => DB::table('tbl_referrers')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /**
     * AJAX: Return a medicine group with its items for auto-populating the Rx table.
     */
    public function ajaxGetMedicineGroup(string $slug, int $id): JsonResponse
    {
        $group = MedicineGroup::with('items.medicine', 'items.dosage', 'items.route')
            ->where('tenant_id', app('tenant')->id)
            ->findOrFail($id);

        return response()->json($group);
    }
}
