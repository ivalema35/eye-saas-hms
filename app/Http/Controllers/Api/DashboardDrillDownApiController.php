<?php

/**
 * DashboardDrillDownApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of 6 dashboard drill-down widget controllers,
 * all shipped with the OT Workflow Upgrade, all had zero API coverage — found
 * during the Round 3.5 full app-parity audit (2026-08-04), lower priority but
 * user asked for full parity:
 *   - Hospital\Dashboard\AdminCollectionController (reception-wise collection + drill-in)
 *   - Hospital\Dashboard\AdminDashboardPatientsController (today-patients report + export)
 *   - Hospital\Dashboard\OtAppointmentListController (OT appointments card drill-down)
 *   - Hospital\Dashboard\DoctorOtListController (doctor's OT bookings drill-down)
 *   - Hospital\Dashboard\AssistantOtListController (OT assistant's queue drill-down)
 *   - Hospital\Dashboard\ReceptionistTotalPatientsController (receptionist collection + list)
 *
 * Grouped into one controller since each is small (one index method, same
 * date-range-filter shape) — mirrors how Phase 7 grouped 3 small OT masters
 * into one OtInventoryApiController rather than 6 near-empty files.
 *
 * PERMISSIONS: copied exactly from routes/hospital.php's OR-groups for each route
 * (see route registration in routes/api.php for the exact key per endpoint).
 */

namespace App\Http\Controllers\Api;

use App\Exports\GenericArrayExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtPreOp;
use App\Models\Hospital\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardDrillDownApiController extends Controller
{
    // ── Admin Collection (reception-wise) ───────────────────────────────────

    public function adminCollectionIndex(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);
        $rows = $this->receptionCollectionRows($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'rows' => $rows->values(),
                'grand_total' => $rows->sum('total'),
            ],
        ]);
    }

    public function adminCollectionShow(string $slug, Request $request, int $receptionId): JsonResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $reception = HospitalUser::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->find($receptionId);

        if (! $reception) {
            return response()->json(['success' => false, 'message' => 'Reception user not found.'], 404);
        }

        $patients = Patient::query()
            ->with(['caseType:id,case_type'])
            ->where('reception_id', $receptionId)
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->get(['id', 'case_id', 'case_fee']);

        $buckets = [
            'new' => ['label' => 'New Case', 'count' => 0, 'total' => 0.0],
            'old' => ['label' => 'Old Case', 'count' => 0, 'total' => 0.0],
            'other' => ['label' => 'Other', 'count' => 0, 'total' => 0.0],
        ];

        foreach ($patients as $patient) {
            $key = $this->caseBucketKey($patient->caseType?->case_type);
            $buckets[$key]['count']++;
            $buckets[$key]['total'] += (float) $patient->case_fee;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reception' => $reception,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'buckets' => $buckets,
                'total' => array_sum(array_column($buckets, 'total')),
                'count' => array_sum(array_column($buckets, 'count')),
            ],
        ]);
    }

    private function receptionCollectionRows(string $startDate, string $endDate)
    {
        $receptions = HospitalUser::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = Patient::query()
            ->selectRaw('reception_id, COUNT(*) as patient_count, COALESCE(SUM(case_fee), 0) as fee_total')
            ->whereNotNull('reception_id')
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->groupBy('reception_id')
            ->get()
            ->keyBy('reception_id');

        return $receptions->map(function (HospitalUser $rec) use ($stats) {
            $row = $stats->get($rec->id);

            return [
                'id' => $rec->id,
                'name' => $rec->name,
                'count' => (int) ($row->patient_count ?? 0),
                'total' => (float) ($row->fee_total ?? 0),
            ];
        })->filter(fn ($row) => $row['count'] > 0 || $row['total'] > 0)->values();
    }

    private function caseBucketKey(?string $caseType): string
    {
        $value = strtolower(trim((string) $caseType));
        if (str_contains($value, 'old')) {
            return 'old';
        }
        if (str_contains($value, 'new')) {
            return 'new';
        }

        return 'other';
    }

    // ── Admin "Today Patients" report ───────────────────────────────────────

    public function adminPatientsIndex(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $patients = $this->adminPatientsQuery($request, $startDate, $endDate)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $patients,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reception_id' => $request->filled('reception_id') ? (int) $request->input('reception_id') : null,
                'doctor_id' => $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null,
                'receptions' => HospitalUser::query()->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['receptionist', 'receptionist_opd']))->orderBy('name')->get(['id', 'name']),
                'doctors' => HospitalUser::query()->whereHas('role', fn (Builder $q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function adminPatientsExport(Request $request): BinaryFileResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $patients = $this->adminPatientsQuery($request, $startDate, $endDate)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get();

        $rows = $patients->map(fn (Patient $patient): array => [
            $patient->full_name,
            $patient->cityName ?: '-',
            $patient->contact_no ?: '-',
            $patient->age ?: '-',
            ($patient->checked_in_at ?? $patient->created_at)?->format('h:i A') ?? '-',
            $patient->slot?->slot_name ?: '-',
            $patient->doctor?->name ? 'Dr. ' . $patient->doctor->name : '-',
            $patient->doctor_patient_no ?: '-',
            $patient->reception?->name ?: '-',
            $patient->appointment_date?->format('d M Y') ?? '-',
        ])->all();

        return Excel::download(new GenericArrayExport($rows, [
            'Patient Name', 'City', 'Contact', 'Age', 'Time', 'Time Slot', 'Doctor', 'Dr. Index', 'Reception', 'Date',
        ]), 'Admin_Patient_Report_' . $startDate . '_to_' . $endDate . '.xlsx');
    }

    private function adminPatientsQuery(Request $request, string $startDate, string $endDate): Builder
    {
        return Patient::query()
            ->with(['doctor:id,name', 'reception:id,name', 'masterCity:id,name', 'location', 'slot:id,slot_name'])
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->when($request->filled('reception_id'), fn (Builder $q) => $q->where('reception_id', (int) $request->input('reception_id')))
            ->when($request->filled('doctor_id'), fn (Builder $q) => $q->where('doctor_id', (int) $request->input('doctor_id')));
    }

    // ── OT Appointments dashboard card ──────────────────────────────────────

    public function otAppointmentsIndex(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $appointments = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name', 'createdBy:id,name'])
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json(['success' => true, 'data' => $appointments, 'meta' => ['start_date' => $startDate, 'end_date' => $endDate]]);
    }

    // ── Doctor's OT bookings drill-down ──────────────────────────────────────

    /**
     * Matches web's `DoctorOtListController::index()` exactly (web pull
     * 2026-08-07, "Phase 2"): also surfaces null-surgery_date bookings
     * (unscheduled since surgery_date/slot_id are now assigned later — see
     * WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §1) when today falls in the
     * selected range, and self-recommended-but-unassigned bookings
     * (`ot_doctor_id` null, `booked_by` = the doctor). New `preOp` eager
     * load + `ot_assistants` list support the two new actions below.
     */
    public function doctorOtIndex(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);
        $doctorId = $request->filled('doctor_id') ? (int) $request->input('doctor_id') : null;

        $query = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no,age', 'otDoctor:id,name', 'otAssistant:id,name', 'preOp'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(surgery_date)'), [$startDate, $endDate])
                    ->orWhere(function ($nullDate) use ($startDate, $endDate) {
                        $today = now()->toDateString();
                        if ($startDate <= $today && $endDate >= $today) {
                            $nullDate->whereNull('surgery_date')
                                ->whereNotIn('ot_status', [
                                    OtBooking::STATUS_OPERATED,
                                    OtBooking::STATUS_DISCHARGED,
                                    OtBooking::STATUS_SURGERY_REFUSED,
                                ]);
                        }
                    });
            });

        if ($doctorId) {
            $query->where(function ($q) use ($doctorId) {
                $q->where('ot_doctor_id', $doctorId)
                    ->orWhere(function ($inner) use ($doctorId) {
                        $inner->whereNull('ot_doctor_id')->where('booked_by', $doctorId);
                    });
            });
        }

        $bookings = $query->orderByDesc('surgery_date')->orderByDesc('id')->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $bookings,
            'meta' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'doctor_id' => $doctorId,
                'doctors' => HospitalUser::query()->whereHas('role', fn ($q) => $q->whereIn('slug', ['doctor', 'ot_doctor']))->orderBy('name')->get(['id', 'name']),
                'ot_assistants' => HospitalUser::query()->where('status', 'active')->whereHas('role', fn ($q) => $q->where('slug', 'ot_assistant'))->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    /**
     * "Doctor agrees OT after consult" — mirrors
     * `DoctorOtListController::assignAssistant()` exactly.
     */
    public function doctorOtAssignAssistant(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->with('preOp')->find($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ((int) $booking->tenant_id !== $tenantId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized booking access.'], 403);
        }

        if (! $this->canManageDoctorOtBooking($booking)) {
            return response()->json(['success' => false, 'message' => 'You can only act on patients assigned to you.'], 403);
        }

        if (! $booking->isDoctorConsultationPending()) {
            return response()->json(['success' => false, 'message' => 'This patient is not awaiting doctor consultation actions.'], 422);
        }

        $validated = $request->validate([
            'ot_assistant_id' => [
                'required', 'integer',
                Rule::exists('hospital_users', 'id')->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                        ->where('status', 'active')
                        ->whereExists(function ($sub) {
                            $sub->selectRaw('1')
                                ->from('roles')
                                ->whereColumn('roles.id', 'hospital_users.role_id')
                                ->where('roles.slug', 'ot_assistant');
                        });
                }),
            ],
        ], [
            'ot_assistant_id.required' => 'Select an OT Assistant to proceed with surgery.',
        ]);

        DB::transaction(function () use ($booking, $validated, $tenantId): void {
            OtPreOp::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
                [
                    'pre_op_status' => OtPreOp::STATUS_READY_FOR_SURGERY,
                    'entered_by' => (int) auth('sanctum')->id(),
                ]
            );

            $booking->update([
                'ot_assistant_id' => (int) $validated['ot_assistant_id'],
                'ot_status' => OtBooking::STATUS_READY,
                'attended_at' => $booking->attended_at ?? now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'OT Assistant assigned. Patient is Ready for OT.', 'data' => ['ot_status' => $booking->fresh()->ot_status]]);
    }

    /**
     * "Patient refuses surgery" — mirrors
     * `DoctorOtListController::refuseSurgery()` exactly.
     */
    public function doctorOtRefuseSurgery(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->with('preOp')->find($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ((int) $booking->tenant_id !== $tenantId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized booking access.'], 403);
        }

        if (! $this->canManageDoctorOtBooking($booking)) {
            return response()->json(['success' => false, 'message' => 'You can only act on patients assigned to you.'], 403);
        }

        if (! $booking->isDoctorConsultationPending()) {
            return response()->json(['success' => false, 'message' => 'This patient is not awaiting doctor consultation actions.'], 422);
        }

        $booking->update([
            'ot_status' => OtBooking::STATUS_SURGERY_REFUSED,
            'ot_assistant_id' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Patient refused OT. Sent to Accounts for refund (status: surgery_refused).', 'data' => ['ot_status' => $booking->ot_status]]);
    }

    /** Mirrors `DoctorOtListController::assertCanManage()`. */
    private function canManageDoctorOtBooking(OtBooking $booking): bool
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperUser') && $user->isSuperUser()) {
            return true;
        }

        $slug = $user->role?->slug;
        if (in_array($slug, ['hospital_admin', 'admin'], true)) {
            return true;
        }

        return (int) $booking->ot_doctor_id === (int) $user->id;
    }

    // ── OT Assistant's queue drill-down ──────────────────────────────────────

    public function assistantOtIndex(Request $request): JsonResponse
    {
        $user = Auth::guard('hospital_user')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $assistantId = (int) $user->id;
        if ($user->role?->slug !== 'ot_assistant' && $request->filled('assistant_id')) {
            $assistantId = (int) $request->input('assistant_id');
        }

        $start = $request->filled('start_date') ? (string) $request->input('start_date') : null;
        $end = $request->filled('end_date') ? (string) $request->input('end_date') : $start;
        if ($start && $end && $end < $start) {
            [$start, $end] = [$end, $start];
        }

        $query = OtBooking::query()
            ->with(['patient:id,first_name,middle_name,last_name,contact_no,age', 'otDoctor:id,name', 'otAssistant:id,name', 'payments'])
            ->where('ot_assistant_id', $assistantId);

        if ($start) {
            $query->whereDate('surgery_date', '>=', $start);
        }
        if ($end) {
            $query->whereDate('surgery_date', '<=', $end);
        }

        $bookings = $query->orderByDesc('surgery_date')->orderByDesc('id')->paginate((int) $request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $bookings,
            'meta' => ['start_date' => $start, 'end_date' => $end, 'assistant_name' => $user->role?->slug === 'ot_assistant' ? $user->name : null],
        ]);
    }

    // ── Receptionist "Total Patients" ────────────────────────────────────────

    public function receptionistTotalPatients(Request $request): JsonResponse
    {
        [$startDate, $endDate] = $this->resolvedDates($request);

        $baseQuery = Patient::query()
            ->with(['doctor:id,name', 'reception:id,name', 'masterCity:id,name', 'location', 'caseType:id,case_type', 'otAppointmentSource:id,converted_patient_id'])
            ->whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate);

        $collection = (float) (clone $baseQuery)->sum('case_fee');
        $patients = (clone $baseQuery)->orderByDesc('appointment_date')->orderByDesc('id')->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $patients,
            'meta' => ['start_date' => $startDate, 'end_date' => $endDate, 'collection' => $collection],
        ]);
    }

    // ── Shared ────────────────────────────────────────────────────────────────

    /** @return array{0: string, 1: string} */
    private function resolvedDates(Request $request): array
    {
        $today = now()->toDateString();
        $start = $request->input('start_date') ?: $today;
        $end = $request->input('end_date') ?: $start;

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
