<?php

namespace App\Http\Controllers\Hospital\Examination;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicalQueueController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $tenantId = app('tenant')->id;
        $user = auth()->guard('hospital_user')->user();

        $filterDate = $request->input('date', Carbon::today()->toDateString());

        $doctorsQuery = HospitalUser::query()
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
            ->get(['id', 'name', 'doctor_type']);

        $doctors = $doctorsQuery->map(function ($doctor) use ($tenantId, $filterDate) {
            $assignedPatients = Patient::where('tenant_id', $tenantId)
                ->where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $filterDate)
                ->count();

            $primaryPending = Patient::where('tenant_id', $tenantId)
                ->where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $filterDate)
                ->whereNull('primary_done_at')
                ->count();

            $secondaryPending = Patient::where('tenant_id', $tenantId)
                ->where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $filterDate)
                ->whereNotNull('primary_done_at')
                ->whereNull('secondary_done_at')
                ->count();

            return [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'doctor_type' => $doctor->doctor_type,
                'total_assigned' => $assignedPatients,
                'primary_pending' => $primaryPending,
                'secondary_pending' => $secondaryPending,
            ];
        })->toArray();

        $selectedDoctorId = null;
        if ($request->has('doctor_id') && $request->doctor_id) {
            $selectedDoctorId = (int) $request->doctor_id;
        } elseif (in_array($user?->role?->slug, ['doctor', 'ot_doctor'], true)) {
            $selectedDoctorId = $user->id;
        }

        $patientQuery = Patient::with(['doctor', 'caseType', 'primaryExamination'])
            ->where('tenant_id', $tenantId)
            ->whereDate('appointment_date', $filterDate);

        if ($selectedDoctorId) {
            $patientQuery->where('doctor_id', $selectedDoctorId);
        }

        $patients = $patientQuery->latest()->get();

        $primaryQueue = $patients->filter(fn ($p) => $p->primary_done_at === null)->values();

        $dilationQueue = $patients->filter(function ($p) {
            if ($p->primary_done_at === null || $p->secondary_done_at !== null) {
                return false;
            }

            $primaryExam = $p->primaryExamination;
            if (! $primaryExam) {
                return false;
            }

            $dilateFlag = $primaryExam->exam_data['dilate'] ?? 'No';
            if ($dilateFlag !== 'Yes' || ! $primaryExam->dilation_time) {
                return false;
            }

            $unlockTime = $primaryExam->updated_at->copy()->addMinutes($primaryExam->dilation_time);

            return now()->lessThan($unlockTime);
        })->values();

        $secondaryQueue = $patients->filter(function ($p) {
            if ($p->primary_done_at === null || $p->secondary_done_at !== null) {
                return false;
            }

            $primaryExam = $p->primaryExamination;
            if (! $primaryExam) {
                return false;
            }

            $dilateFlag = $primaryExam->exam_data['dilate'] ?? 'No';
            if ($dilateFlag !== 'Yes' || ! $primaryExam->dilation_time) {
                return true;
            }

            $unlockTime = $primaryExam->updated_at->copy()->addMinutes($primaryExam->dilation_time);

            return now()->greaterThanOrEqualTo($unlockTime);
        })->values();

        return view('hospital.exam.queue', compact(
            'slug',
            'filterDate',
            'doctors',
            'selectedDoctorId',
            'primaryQueue',
            'dilationQueue',
            'secondaryQueue'
        ));
    }
}
