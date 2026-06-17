<?php

namespace App\Http\Controllers\Hospital\Patient;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientHistoryController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $search     = $request->input('search');
        $rawIds     = $request->input('patient_ids', '');
        $patientIds = array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        $tenantId   = app('tenant')->id;

        $patient    = null;
        $history    = collect();
        $nameGroups = collect();

        if ($patientIds) {
            // A name-group card was clicked — load combined history for all IDs in that group
            $patient = Patient::find($patientIds[0]);
            $history = $this->loadExamHistoryForIds($patientIds);
        } elseif ($search) {
            $candidates = Patient::where(function ($q) use ($search) {
                $q->where('patient_code', $search)
                    ->orWhere('contact_no', $search)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            })->get();

            if ($candidates->count() === 1) {
                $patient = $candidates->first();
                $history = $this->loadExamHistoryForIds([$patient->id]);
            } elseif ($candidates->count() > 1) {
                // Group candidates by normalised full name
                $groups = $candidates->groupBy(
                    fn($p) => mb_strtolower(trim($p->first_name . ' ' . $p->last_name))
                );

                if ($groups->count() === 1) {
                    // All registrations belong to the same person → auto-merge history
                    $patient = $candidates->sortByDesc('id')->first();
                    $history = $this->loadExamHistoryForIds($candidates->pluck('id')->all());
                } else {
                    // Distinct names → show one disambiguation card per unique name
                    $nameGroups = $groups->map(fn($group) => (object)[
                        'display_name' => trim($group->first()->first_name . ' ' . $group->first()->last_name),
                        'patient_code' => $group->first()->patient_code,
                        'age'          => $group->first()->age,
                        'gender'       => $group->first()->gender,
                        'patient_ids'  => $group->pluck('id')->implode(','),
                        'count'        => $group->count(),
                    ])->values();
                }
            }
        }

        $masters      = $this->loadMasters($tenantId);
        $historyRoute = route('hospital.patients.history', ['slug' => $slug]);

        return view('hospital.patient.history',
            compact('patient', 'history', 'search', 'slug', 'nameGroups', 'historyRoute') + $masters
        );
    }

    public function print(string $slug, Patient $patient): View
    {
        $tenantId = app('tenant')->id;
        $patient->load('location');
        $history  = $this->loadExamHistoryForIds([$patient->id]);
        $masters  = $this->loadMasters($tenantId);

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
                'id'           => $p->id,
                'patient_code' => $p->patient_code,
                'name'         => trim($p->first_name . ' ' . $p->last_name),
                'age'          => $p->age,
                'gender'       => $p->gender,
            ]);

        return response()->json($patients);
    }

    /**
     * Load primary + secondary exams for one or more patient_ids and sort chronologically.
     * Accepts an array so same-name groups (multiple registrations of one person)
     * can be combined into a single timeline without merging unrelated patients.
     */
    private function loadExamHistoryForIds(array $patientIds): Collection
    {
        $primaryExams = PrimaryExamination::with(['doctor', 'prescriptions.medicine', 'prescriptions.dosage'])
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function (PrimaryExamination $exam): PrimaryExamination {
                $exam->type  = 'Primary Exam';
                $exam->color = 'primary';
                $exam->icon  = 'bi-clipboard2-pulse';
                return $exam;
            });

        $secondaryExams = SecondaryExamination::with('doctor')
            ->whereIn('patient_id', $patientIds)
            ->get()
            ->map(function (SecondaryExamination $exam): SecondaryExamination {
                $exam->type  = 'Secondary Exam';
                $exam->color = 'secondary';
                $exam->icon  = 'bi-clipboard2-check';
                return $exam;
            });

        return $primaryExams->concat($secondaryExams)->sortByDesc('examined_at');
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
