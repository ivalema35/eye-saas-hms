<?php

namespace App\Http\Controllers\Hospital\Patient;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Dosage;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * PatientHistoryController
 *
 * Allows searching for a patient by MRD, contact, or name and displaying
 * a chronological clinical timeline of all their examinations.
 */
class PatientHistoryController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $search = $request->input('search');
        $tenantId = app('tenant')->id;

        [$patient, $history] = $this->loadHistory($search, null, $tenantId);

        $masters = $this->loadMasters($tenantId);

        return view('hospital.patient.history', compact('patient', 'history', 'search', 'slug') + $masters);
    }

    public function print(string $slug, Patient $patient): View
    {
        $tenantId = app('tenant')->id;
        $patient->load('location');
        [$patient, $history] = $this->loadHistory($patient->patient_code, $patient, $tenantId);

        $masters = $this->loadMasters($tenantId);

        return view('hospital.patient.history-print', compact('patient', 'history', 'slug') + $masters);
    }

    /**
     * @return array{0: Patient|null, 1: Collection}
     */
    private function loadHistory(?string $search, ?Patient $patient = null, int $tenantId = 0): array
    {
        $history = collect();

        if ($patient === null && $search) {
            $patient = Patient::where(function ($q) use ($search) {
                $q->where('patient_code', $search)
                    ->orWhere('contact_no', $search)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            })->first();
        }

        if ($patient) {
            $patientIds = Patient::where('contact_no', $patient->contact_no)->pluck('id');

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

            $history = $primaryExams->concat($secondaryExams)->sortByDesc('examined_at');
        }

        return [$patient, $history];
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
