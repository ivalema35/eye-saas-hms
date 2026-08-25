<?php

/**
 * OtAppointmentController.php
 *
 * PURPOSE: OT Workflow Upgrade — Phase 2 (Appointment Module).
 *          Pre-registration appointments — booked over phone/walk-in/online/OT
 *          BEFORE the patient physically arrives. Reception check-in (Patient module)
 *          searches these by appointment number/mobile to pre-fill OPD registration.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §2.
 *
 * PERMISSIONS: ot.appointment.view (index/search), ot.appointment.create (store),
 *              ot.appointment.edit (update/cancel/confirm)
 */

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtAppointment;
use App\Models\Hospital\OT\OtSlot;
use App\Models\Hospital\Referrer;
use App\Models\Platform\MasterCity;
use App\Support\PhoneRules;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtAppointmentController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $status = strtolower((string) $request->query('status', 'all'));
        if (!in_array($status, ['all', OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED, OtAppointment::STATUS_CANCELLED, OtAppointment::STATUS_COMPLETED], true)) {
            $status = 'all';
        }

        $query = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name', 'convertedPatient.latestOtBooking']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($date = $request->query('date')) {
            $query->whereDate('appointment_date', $date);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('mobile_no', 'like', "%{$search}%");

                if (str_starts_with(strtoupper($search), 'APT-') && ctype_digit(ltrim(substr($search, 4), '0'))) {
                    $q->orWhere('appointment_seq', (int) ltrim(substr($search, 4), '0'));
                }
            });
        }

        $appointments = $query
            ->orderByDesc('appointment_date')
            ->orderByDesc('id')
            ->get();

        return view('hospital.ot.appointments.index', [
            'slug' => $slug,
            'appointments' => $appointments,
            'activeStatus' => $status,
        ]);
    }

    public function create(string $slug): View
    {
        $tenantId = (int) app('tenant')->id;

        $doctors = $this->activeDoctors($tenantId);
        $locations = $this->citiesForCurrentHospital();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $slots = OtSlot::query()->orderBy('start_time')->get();

        $nextSeq = OtAppointment::peekNextSequenceForTenant($tenantId);
        $nextAppointmentNumber = OtAppointment::formatAppointmentNumber($nextSeq);

        $loadDate = now()->toDateString();

        return view('hospital.ot.appointments.create', [
            'slug' => $slug,
            'doctors' => $doctors,
            'locations' => $locations,
            'referrers' => $referrers,
            'slots' => $slots,
            'nextAppointmentNumber' => $nextAppointmentNumber,
            'doctorLoadDate' => $loadDate,
            'doctorLoadCards' => $this->buildDoctorSlotLoad($doctors, $slots, $loadDate),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'appointment_type' => [
                'required',
                Rule::in([
                    OtAppointment::TYPE_PHONE,
                    OtAppointment::TYPE_WALK_IN,
                    OtAppointment::TYPE_ONLINE,
                    OtAppointment::TYPE_OT,
                ])
            ],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'doctor_id' => ['required', 'integer', Rule::exists('hospital_users', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId))],
            'patient_name' => ['required', 'string', 'max:200'],
            'middle_name' => ['nullable', 'string', 'max:200'],
            'surname' => ['required', 'string', 'max:200'],
            'mobile_no' => PhoneRules::required(20),
            'whatsapp_no' => PhoneRules::nullable(20),
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'referrer_id' => ['nullable', 'integer', Rule::exists('tbl_referrers', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId))],
            'location_id' => ['required', 'integer', 'exists:tbl_master_cities,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], array_merge(PhoneRules::messages('mobile_no'), PhoneRules::messages('whatsapp_no')));

        DB::transaction(function () use ($validated, $tenantId): void {
            OtAppointment::create([
                ...$validated,
                'tenant_id' => $tenantId,
                'appointment_seq' => OtAppointment::allocateNextSequenceForTenant($tenantId),
                'status' => OtAppointment::STATUS_BOOKED,
                'created_by' => (int) auth('hospital_user')->id(),
            ]);
        });

        return redirect()
            ->route('hospital.dashboard', ['slug' => $slug])
            ->with('success', 'Appointment booked successfully.');
    }

    public function edit(string $slug, int $id): View
    {
        $appointment = OtAppointment::query()->findOrFail($id);

        $tenantId = (int) app('tenant')->id;

        $doctors = $this->activeDoctors($tenantId);
        $locations = $this->citiesForCurrentHospital();
        $referrers = Referrer::where('tenant_id', $tenantId)->orderBy('name')->get();
        $slots = OtSlot::query()->orderBy('start_time')->get();

        $loadDate = optional($appointment->appointment_date)->format('Y-m-d') ?: now()->toDateString();

        return view('hospital.ot.appointments.edit', [
            'slug' => $slug,
            'appointment' => $appointment,
            'doctors' => $doctors,
            'locations' => $locations,
            'referrers' => $referrers,
            'slots' => $slots,
            'doctorLoadDate' => $loadDate,
            'doctorLoadCards' => $this->buildDoctorSlotLoad($doctors, $slots, $loadDate, (int) $appointment->id),
        ]);
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;
        $appointment = OtAppointment::query()->findOrFail($id);

        $validated = $request->validate([
            'appointment_type' => [
                'required',
                Rule::in([
                    OtAppointment::TYPE_PHONE,
                    OtAppointment::TYPE_WALK_IN,
                    OtAppointment::TYPE_ONLINE,
                    OtAppointment::TYPE_OT,
                ])
            ],
            'appointment_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'doctor_id' => ['required', 'integer', Rule::exists('hospital_users', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId))],
            'patient_name' => ['required', 'string', 'max:200'],
            'middle_name' => ['nullable', 'string', 'max:200'],
            'surname' => ['required', 'string', 'max:200'],
            'mobile_no' => PhoneRules::required(20),
            'whatsapp_no' => PhoneRules::nullable(20),
            'age' => ['required', 'integer', 'min:0', 'max:150'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'referrer_id' => ['nullable', 'integer', Rule::exists('tbl_referrers', 'id')->where(fn($q) => $q->where('tenant_id', $tenantId))],
            'location_id' => ['required', 'integer', 'exists:tbl_master_cities,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], array_merge(PhoneRules::messages('mobile_no'), PhoneRules::messages('whatsapp_no')));

        $appointment->update($validated);

        return redirect()
            ->route('hospital.ot.appointments.index', ['slug' => $slug])
            ->with('success', 'Appointment updated successfully.');
    }

    public function confirm(string $slug, int $id): RedirectResponse
    {
        $appointment = OtAppointment::query()->findOrFail($id);
        $appointment->update(['status' => OtAppointment::STATUS_CONFIRMED]);

        return redirect()->back()->with('success', 'Appointment confirmed.');
    }

    public function cancel(string $slug, int $id): RedirectResponse
    {
        $appointment = OtAppointment::query()->findOrFail($id);
        $appointment->update(['status' => OtAppointment::STATUS_CANCELLED]);

        return redirect()->back()->with('success', 'Appointment cancelled.');
    }

    /**
     * AJAX search used by Reception check-in (Patient registration) — matches by
     * appointment number (APT-000123), mobile number, or patient name; returns only
     * today's booked/confirmed appointments not yet converted/cancelled.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return response()->json(['found' => false, 'appointments' => []]);
        }

        $query = OtAppointment::query()
            ->with(['doctor:id,name', 'location:id,name'])
            ->whereDate('appointment_date', Carbon::today())
            ->whereIn('status', [OtAppointment::STATUS_BOOKED, OtAppointment::STATUS_CONFIRMED])
            ->whereNull('converted_patient_id');

        if (preg_match('/^APT-?0*(\d+)$/i', $term, $matches)) {
            $query->where('appointment_seq', (int) $matches[1]);
        } else {
            $query->where(function ($q) use ($term): void {
                $q->where('mobile_no', 'like', "%{$term}%")
                    ->orWhere('patient_name', 'like', "%{$term}%");
            });
        }

        $appointments = $query->latest('appointment_date')->limit(10)->get();

        return response()->json([
            'found' => $appointments->isNotEmpty(),
            'appointments' => $appointments->map(fn(OtAppointment $appointment) => [
                'id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'patient_name' => $appointment->patient_name,
                'middle_name' => $appointment->middle_name,
                'surname' => $appointment->surname,
                'mobile_no' => $appointment->mobile_no,
                'whatsapp_no' => $appointment->whatsapp_no,
                'age' => $appointment->age,
                'gender' => $appointment->gender,
                'occupation' => $appointment->occupation,
                'referrer_id' => $appointment->referrer_id,
                'location_id' => $appointment->location_id,
                'doctor_id' => $appointment->doctor_id,
                'doctor_name' => $appointment->doctor?->name,
                'appointment_date' => optional($appointment->appointment_date)->format('d M Y'),
            ])->values(),
        ]);
    }

    /**
     * AJAX lookup used on the appointment form — when the user picks a date + time
     * slot, shows which patients are already booked in that same slot so double
     * bookings are visible before saving.
     */
    public function slotAppointments(Request $request, string $slug): JsonResponse
    {
        $date = $request->query('date');
        $time = $request->query('time');

        if (!$date || !$time) {
            return response()->json(['appointments' => []]);
        }

        $query = OtAppointment::query()
            ->whereDate('appointment_date', $date)
            ->whereTime('appointment_time', $time)
            ->where('status', '!=', OtAppointment::STATUS_CANCELLED);

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', (int) $request->query('doctor_id'));
        }

        if ($excludeId = $request->query('exclude_id')) {
            $query->where('id', '!=', (int) $excludeId);
        }

        $appointments = $query->orderBy('id')->get(['id', 'patient_name', 'surname', 'status']);

        return response()->json([
            'appointments' => $appointments->map(fn(OtAppointment $appointment) => [
                'id' => $appointment->id,
                'name' => trim(collect([$appointment->patient_name, $appointment->surname])->filter()->implode(' ')),
                'status' => $appointment->status,
            ])->values(),
        ]);
    }

    /**
     * AJAX: per-doctor load cards for a date — each doctor lists every time slot + count.
     * Example UI line: "1 to 2 : 10"
     */
    public function doctorSlotLoad(Request $request, string $slug): JsonResponse
    {
        $date = $request->query('date') ?: now()->toDateString();
        $excludeId = $request->filled('exclude_id') ? (int) $request->query('exclude_id') : null;

        $tenantId = (int) app('tenant')->id;
        $doctors = $this->activeDoctors($tenantId);
        $slots = OtSlot::query()->orderBy('start_time')->get();

        return response()->json([
            'date' => $date,
            'doctors' => $this->buildDoctorSlotLoad($doctors, $slots, $date, $excludeId),
        ]);
    }

    /**
     * City dropdown scoped to the hospital's own country — same filter used by
     * the OPD walk-in patient form (PatientController@create). Without this,
     * every city in the platform-wide master list shows up regardless of which
     * country the hospital was registered under.
     */
    private function citiesForCurrentHospital()
    {
        $hospitalCountry = auth('hospital_user')->user()?->tenant?->country;

        return MasterCity::query()
            ->with(['district', 'state'])
            ->when($hospitalCountry, function ($query) use ($hospitalCountry): void {
                $query->whereHas('state.country', function ($q) use ($hospitalCountry): void {
                    $q->where('name', $hospitalCountry);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'district_id', 'state_id']);
    }

    private function activeDoctors(int $tenantId): Collection
    {
        return HospitalUser::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('role', function ($query): void {
                $query->whereIn('slug', ['doctor']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  Collection<int, HospitalUser>  $doctors
     * @param  Collection<int, OtSlot>  $slots
     * @return list<array{id:int,name:string,total:int,slots:list<array{time:string,label:string,count:int,display:string}>}>
     */
    private function buildDoctorSlotLoad(Collection $doctors, Collection $slots, string $date, ?int $excludeId = null): array
    {
        $query = OtAppointment::query()
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', OtAppointment::STATUS_CANCELLED)
            ->whereNotNull('doctor_id');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $counts = [];
        foreach ($query->get(['doctor_id', 'appointment_time']) as $row) {
            $doctorId = (int) $row->doctor_id;
            $timeKey = '';
            if ($row->appointment_time) {
                try {
                    $timeKey = Carbon::parse($row->appointment_time)->format('H:i');
                } catch (\Throwable) {
                    $timeKey = substr((string) $row->appointment_time, 0, 5);
                }
            }
            $key = $doctorId . '|' . $timeKey;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $slotMeta = $slots->map(function (OtSlot $slot) {
            $time = '';
            if ($slot->start_time) {
                try {
                    $time = Carbon::parse($slot->start_time)->format('H:i');
                } catch (\Throwable) {
                    $time = '';
                }
            }

            return [
                'time' => $time,
                'label' => $this->slotRangeLabel($slot),
            ];
        })->values();

        return $doctors->map(function (HospitalUser $doctor) use ($slotMeta, $counts) {
            $lines = [];
            $total = 0;
            $matchedTimes = [];

            foreach ($slotMeta as $slot) {
                $count = (int) ($counts[$doctor->id . '|' . $slot['time']] ?? 0);
                $total += $count;
                if ($slot['time'] !== '') {
                    $matchedTimes[$slot['time']] = true;
                }
                $lines[] = [
                    'time' => $slot['time'],
                    'label' => $slot['label'],
                    'count' => $count,
                    'display' => $slot['label'] . ' : ' . $count,
                ];
            }

            // Times that do not match any master slot start_time
            $other = 0;
            $prefix = $doctor->id . '|';
            foreach ($counts as $key => $count) {
                if (!str_starts_with((string) $key, $prefix)) {
                    continue;
                }
                $time = substr((string) $key, strlen($prefix));
                if ($time === '' || isset($matchedTimes[$time])) {
                    continue;
                }
                $other += (int) $count;
            }

            if ($other > 0) {
                $total += $other;
                $lines[] = [
                    'time' => '',
                    'label' => 'Other',
                    'count' => $other,
                    'display' => 'Other : ' . $other,
                ];
            }

            // No OT slots master → show single total line for the day
            if ($lines === []) {
                $dayTotal = 0;
                foreach ($counts as $key => $count) {
                    if (str_starts_with((string) $key, $prefix)) {
                        $dayTotal += (int) $count;
                    }
                }
                $total = $dayTotal;
                $lines[] = [
                    'time' => '',
                    'label' => 'All',
                    'count' => $dayTotal,
                    'display' => 'All : ' . $dayTotal,
                ];
            }

            return [
                'id' => (int) $doctor->id,
                'name' => $doctor->name,
                'total' => $total,
                'slots' => $lines,
            ];
        })->values()->all();
    }

    private function slotRangeLabel(OtSlot $slot): string
    {
        if (!$slot->start_time || !$slot->end_time) {
            return (string) ($slot->slot_name ?: 'Slot');
        }

        try {
            $start = Carbon::parse($slot->start_time);
            $end = Carbon::parse($slot->end_time);
        } catch (\Throwable) {
            return (string) ($slot->slot_name ?: 'Slot');
        }

        // "1 to 2" when both times sit on the hour; otherwise "1:30 to 2:15"
        if ($start->minute === 0 && $end->minute === 0) {
            return $start->format('g') . ' to ' . $end->format('g');
        }

        return $start->format('g:i') . ' to ' . $end->format('g:i');
    }
}
