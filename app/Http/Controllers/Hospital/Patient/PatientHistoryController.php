<?php

namespace App\Http\Controllers\Hospital\Patient;

use App\Http\Controllers\Controller;
use App\Models\Hospital\Patient;
use App\Models\Hospital\PrimaryExamination;
use App\Models\Hospital\SecondaryExamination;
use Illuminate\Http\Request;
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
        $patient = null;
        $history = collect();

        if ($search) {
            // Wrap OR conditions in a closure so the global BelongsToTenant scope
            // is not bypassed — ensures WHERE tenant_id = X AND (col = ? OR ...)
            $patient = Patient::where(function ($q) use ($search) {
                $q->where('patient_code', $search)
                    ->orWhere('contact_no', $search)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            })->first();

            if ($patient) {
                $primaryExams = PrimaryExamination::with('doctor')
                    ->where('patient_id', $patient->id)
                    ->get()
                    ->map(function (PrimaryExamination $exam): PrimaryExamination {
                        $exam->type = 'Primary Exam';
                        $exam->color = 'primary';
                        $exam->icon = 'bi-clipboard2-pulse';

                        return $exam;
                    });

                $secondaryExams = SecondaryExamination::with('doctor')
                    ->where('patient_id', $patient->id)
                    ->get()
                    ->map(function (SecondaryExamination $exam): SecondaryExamination {
                        $exam->type = 'Secondary Exam';
                        $exam->color = 'secondary';
                        $exam->icon = 'bi-clipboard2-check';

                        return $exam;
                    });

                $history = $primaryExams->concat($secondaryExams)->sortByDesc('examined_at');
            }
        }

        return view('hospital.patient.history', compact('patient', 'history', 'search', 'slug'));
    }
}
