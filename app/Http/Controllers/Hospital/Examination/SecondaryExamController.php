<?php

namespace App\Http\Controllers\Hospital\Examination;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hospital\Examination\StoreSecondaryExamRequest;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\MasterAdvice;
use App\Models\Hospital\Medicine;
use App\Models\Hospital\MedicineGroup;
use App\Models\Hospital\MedicineRoute;
use App\Models\Hospital\Patient;
use App\Models\Hospital\SecondaryExamination;
use App\Services\Hospital\ExaminationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Secondary Eye Examination Controller
 *
 * Specialist follow-up exam. Shows primary exam data as read-only reference
 * and allows the secondary doctor to record their own findings.
 *
 * URL: /{slug}/exam/secondary/{id}
 */
class SecondaryExamController extends Controller
{
    public function __construct(private ExaminationService $examinationService) {}

    public function show(string $slug, int $id): View|RedirectResponse
    {
        $tenantId = app('tenant')->id;
        $patient = Patient::with('primaryExamination.prescriptions.medicine', 'primaryExamination.prescriptions.dosage')
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);
        $primaryExam = $patient->primaryExamination;

        $secondaryExam = SecondaryExamination::where('patient_id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        // Dilation lock: block only if exam not yet saved and timer still running (bypass with ?force=1)
        if (! $secondaryExam && $primaryExam && ($primaryExam->exam_data['dilate'] ?? 'No') === 'Yes' && $primaryExam->dilation_time && ! request()->boolean('force')) {
            $unlockTime = $primaryExam->updated_at->addMinutes($primaryExam->dilation_time);
            if (now()->lessThan($unlockTime)) {
                $minutesLeft = (int) now()->diffInMinutes($unlockTime) + 1;

                return redirect()->back()->with('error', "Patient is currently dilating. Secondary Exam unlocks in {$minutesLeft} minute(s).");
            }
        }
        $user = Auth::guard('hospital_user')->user();
        $ed = $secondaryExam?->exam_data ?? ($primaryExam?->exam_data ?? []);

        $doctors = HospitalUser::query()
            ->where('tenant_id', $tenantId)
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
        $focReceptionists = HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->active()
            ->whereHas('role', fn ($q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($doctors->isEmpty() && $user !== null) {
            $doctors = collect([$user->only(['id', 'name'])]);
        }

        $currentDoctorId = old('doctor_id')
            ?? $secondaryExam?->doctor_id
            ?? (($user?->role?->slug === 'doctor') ? $user->id : $patient->doctor_id);

        $masters = $this->loadMasters($tenantId);
        $masters['doctors'] = $doctors;
        $exam = $secondaryExam;
        $secondaryMedicines = collect($secondaryExam?->exam_data['rx'] ?? []);
        $primaryMedicines = collect($primaryExam?->prescriptions ?? [])->map(function ($prescription): array {
            return [
                'medicine_id' => $prescription->medicine_id,
                'name' => $prescription->medicine?->brand_name ?: $prescription->medicine?->name,
                'dosage_id' => $prescription->dosage_id,
                'duration' => $prescription->duration,
                'instructions' => $prescription->instructions,
                'eye' => $prescription->eye,
            ];
        });
        $initialMedicines = old('medicines')
            ? collect(old('medicines'))
            : ($secondaryMedicines->isNotEmpty() ? $secondaryMedicines : $primaryMedicines);

        return view('hospital.exam.secondary', compact(
            'patient', 'primaryExam', 'secondaryExam', 'exam', 'ed', 'slug',
            'doctors', 'currentDoctorId', 'masters', 'focReceptionists', 'initialMedicines'
        ));
    }

    public function save(StoreSecondaryExamRequest $request, string $slug, int $id): RedirectResponse
    {
        $patient = Patient::findOrFail($id);
        $tenant = app('tenant');
        $data = $request->validated();
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

        $this->examinationService->saveSecondaryExam(
            $patient,
            (int) $data['doctor_id'],
            $data['exam_data'] ?? [],
            $medicines,
            $tenant->id,
            $data['advice'] ?? null
        );

        $user = Auth::guard('hospital_user')->user();

        if (in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true)) {
            return redirect()
                ->route('hospital.dashboard', ['slug' => $slug]) 
                ->with('success', 'Secondary examination saved successfully.');
        }

        return redirect()
            ->route('hospital.exam.secondary.show', ['slug' => $slug, 'id' => $id])
            ->with('success', 'Secondary examination saved successfully.');
    }

    /**
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
}
