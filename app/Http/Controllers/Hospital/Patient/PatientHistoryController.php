<?php

namespace App\Http\Controllers\Hospital\Patient;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use App\Models\Platform\HospitalShareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientHistoryController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $tenantId = app('tenant')->id;
        $rawIds = $request->input('patient_ids', '');
        $patientIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));

        // ── Detail mode: show clinical timeline for a specific patient ────────
        if ($patientIds) {
            $patient = Patient::with(['location', 'masterCity.district', 'masterCity.state'])->find($patientIds[0]);
            $history = $this->loadExamHistoryForIds($patientIds);
            $masters = $this->loadMasters($tenantId);
            $nameGroups = collect();
            $search = null;
            $historyRoute = route('hospital.patients.history', ['slug' => $slug]);

            // Load history from connected partner hospitals for the same contact_no
            $partnerHistoryGroups = collect();
            if ($patient && $patient->contact_no) {
                $currentTenant = app('tenant');
                $partnerTenantIds = HospitalShareRequest::where(function ($q) use ($currentTenant) {
                    $q->where('from_tenant_id', $currentTenant->id)
                        ->orWhere('to_tenant_id', $currentTenant->id);
                })
                    ->where('status', 'accepted')
                    ->get()
                    ->map(fn($r) => $r->from_tenant_id === $currentTenant->id ? $r->to_tenant_id : $r->from_tenant_id)
                    ->toArray();

                if (!empty($partnerTenantIds)) {
                    $partnerPatients = Patient::withoutTenantScope()
                        ->whereIn('tenant_id', $partnerTenantIds)
                        ->where('contact_no', $patient->contact_no)
                        ->with(['tenant:id,name,city,state'])
                        ->get();

                    foreach ($partnerPatients->groupBy('tenant_id') as $partnerTenantId => $group) {
                        $partnerExamHistory = $this->loadPartnerExamHistoryForIds($group->pluck('id')->all());
                        if ($partnerExamHistory->isNotEmpty()) {
                            $partnerDiagnosisMasters = DB::table('tbl_master_diagnosis')
                                ->where('tenant_id', $partnerTenantId)
                                ->orderBy('id')
                                ->get(['id', DB::raw('value as diagnosis')]);
                            $partnerHistoryGroups->push([
                                'tenant' => $group->first()->tenant,
                                'history' => $partnerExamHistory,
                                'diagnosisMasters' => $partnerDiagnosisMasters,
                            ]);
                        }
                    }
                }
            }

            return view(
                'hospital.patient.history',
                compact('patient', 'history', 'search', 'slug', 'nameGroups', 'historyRoute', 'partnerHistoryGroups') + $masters
            );
        }

        // ── List mode: filter + table (same layout as partner history) ────────
        $patientName = $request->input('patient_name');
        $doctorName = $request->input('doctor_name');
        $contactNo = $request->input('contact_no');
        $date = $request->input('date');

        $allPatients = Patient::with(['doctor'])
            ->where(function ($q) {
                $q->whereNotNull('primary_done_at')->orWhereNotNull('secondary_done_at');
            })
            ->when($patientName, fn($q) => $q->where(function ($q2) use ($patientName) {
                $q2->where('first_name', 'like', "%{$patientName}%")
                    ->orWhere('last_name', 'like', "%{$patientName}%");
            }))
            ->when($doctorName, fn($q) => $q->whereHas(
                'doctor',
                fn($q2) => $q2->where('name', 'like', "%{$doctorName}%")
            ))
            ->when($contactNo, fn($q) => $q->where('contact_no', 'like', "%{$contactNo}%"))
            ->when($date, fn($q) => $q->whereDate('appointment_date', $date))
            ->latest('appointment_date')
            ->get();

        $grouped = $allPatients
            ->groupBy(fn($p) => mb_strtolower(trim($p->first_name . ' ' . $p->last_name)) . '|' . $p->contact_no)
            ->map(function ($group) {
                $rep = $group->sortByDesc('id')->first();
                $rep->all_patient_ids = $group->pluck('id')->implode(',');

                return $rep;
            })
            ->values();

        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $patients = new LengthAwarePaginator(
            $grouped->slice(($page - 1) * $perPage, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view(
            'hospital.patient.history',
            compact('slug', 'patients', 'patientName', 'doctorName', 'contactNo', 'date')
        );
    }

    public function print(string $slug, Patient $patient): View
    {
        $tenantId = app('tenant')->id;
        $patient->load(['location', 'masterCity.district', 'masterCity.state']);
        $history = $this->loadExamHistoryForIds([$patient->id]);
        $masters = $this->loadMasters($tenantId);

        return view('hospital.patient.history-print', compact('patient', 'history', 'slug') + $masters);
    }

    /**
     * AJAX: Return all patients matching a given phone number as JSON.
     * Useful for building phone → patient-list UIs without a full page reload.
     */
    public function getPatientsByPhone(Request $request, string $slug): JsonResponse
    {
        $phone = $request->input('phone', '');

        $patients = Patient::where('contact_no', $phone)
            ->get(['id', 'patient_code', 'first_name', 'last_name', 'age', 'gender'])
            ->map(fn($p) => [
                'id' => $p->id,
                'patient_code' => $p->patient_code,
                'name' => trim($p->first_name . ' ' . $p->last_name),
                'age' => $p->age,
                'gender' => $p->gender,
            ]);

        return response()->json($patients);
    }

    /**
     * Load primary + secondary exams for one or more patient_ids and sort chronologically.
     * Accepts an array so same-name groups (multiple registrations of one person)
     * can be combined into a single timeline without merging unrelated patients.
     */
    private function loadPartnerExamHistoryForIds(array $patientIds): Collection
    {
        $primaryExams = PrimaryExamination::withoutGlobalScope('tenant')
            ->with([
                'doctor' => fn($q) => $q->withoutGlobalScopes(),
                'prescriptions.medicine' => fn($q) => $q->withoutGlobalScopes(),
                'prescriptions.dosage',
            ])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function (PrimaryExamination $exam): PrimaryExamination {
                $exam->type = 'Primary Exam';
                $exam->color = 'primary';
                $exam->icon = 'bi-clipboard2-pulse';

                return $exam;
            })
            ->keyBy('patient_id');

        $secondaryExams = SecondaryExamination::withoutGlobalScope('tenant')
            ->with(['doctor' => fn($q) => $q->withoutGlobalScopes()])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function (SecondaryExamination $exam): SecondaryExamination {
                $exam->type = 'Secondary Exam';
                $exam->color = 'secondary';
                $exam->icon = 'bi-clipboard2-check';

                return $exam;
            })
            ->keyBy('patient_id');

        // One exam per visit: secondary once done, primary until then.
        // (union, not merge — merge() re-indexes integer keys via array_merge and would keep both)
        return $secondaryExams->union($primaryExams)->values()->sortByDesc('examined_at');
    }

    private function loadExamHistoryForIds(array $patientIds): Collection
    {
        $primaryExams = PrimaryExamination::with(['doctor', 'prescriptions.medicine', 'prescriptions.dosage'])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function (PrimaryExamination $exam): PrimaryExamination {
                $exam->type = 'Primary Exam';
                $exam->color = 'primary';
                $exam->icon = 'bi-clipboard2-pulse';

                return $exam;
            })
            ->keyBy('patient_id');

        $secondaryExams = SecondaryExamination::with('doctor')
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function (SecondaryExamination $exam): SecondaryExamination {
                $exam->type = 'Secondary Exam';
                $exam->color = 'secondary';
                $exam->icon = 'bi-clipboard2-check';

                return $exam;
            })
            ->keyBy('patient_id');

        // One exam per visit: secondary once done, primary until then.
        // (union, not merge — merge() re-indexes integer keys via array_merge and would keep both)
        return $secondaryExams->union($primaryExams)->values()->sortByDesc('examined_at');
    }

    private function loadMasters(int $tenantId): array
    {
        return [
            'diagnosisMasters' => DB::table('tbl_master_diagnosis')
                ->where('tenant_id', $tenantId)
                ->orderBy('id')
                ->get(['id', DB::raw('value as diagnosis')]),
            'dosageMasters' => Dosage::all(['id', 'dosage'])
                ->keyBy('id'),
        ];
    }
}
