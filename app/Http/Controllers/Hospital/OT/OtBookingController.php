<?php

namespace App\Http\Controllers\Hospital\OT;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtCounselling;
use App\Models\Hospital\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtBookingController extends Controller
{
    public function index(Request $request, string $slug): RedirectResponse
    {
        // Manual OT Bookings list removed — surgery cases come from Recommend Surgery → Counselling.
        return redirect()
            ->route('hospital.ot.counsellor.dashboard', ['slug' => $slug])
            ->with('info', 'OT Bookings list is retired. Use OT Appointments and Counselling instead.');
    }

    public function create(string $slug): RedirectResponse
    {
        // Manual "New OT Booking" removed — use OT Appointment, then doctor Recommend Surgery.
        return redirect()
            ->route('hospital.ot.appointments.create', ['slug' => $slug])
            ->with('info', 'Manual OT Booking is retired. Create an OT Appointment instead.');
    }

    /**
     * NOTE (OT Workflow Upgrade — Phase 1): Booking only captures scheduling data.
     * Mediclaim, lens selection, package/cost estimate and consent are now the
     * Counsellor's job (OtCounsellorController), entered AFTER this booking exists.
     * See docs/OT_WORKFLOW_UPGRADE_PRD.md §1.
     *
     * Manual store disabled — bookings are created via recommendSurgery() from exam.
     */
    public function store(Request $request, string $slug): RedirectResponse
    {
        return redirect()
            ->route('hospital.ot.appointments.index', ['slug' => $slug])
            ->with('error', 'Manual OT Booking is disabled. Use OT Appointment + Doctor Recommend Surgery.');
    }

    /**
     * OT 1.0 Remaining PRD — Phase A1.
     * Doctor Exam → create/update OtBooking as surgery_recommended for Counsellor queue.
     */
    public function recommendSurgery(Request $request, string $slug, int $patientId): RedirectResponse
    {
        $tenantId = (int) app('tenant')->id;

        $patient = Patient::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($patientId);

        $validated = $request->validate([
            'eye' => ['required', Rule::in(['RE', 'LE', 'Both'])],
            // Doctor + OT Assistant, and OT date/slot, are set later in the OT flow.
            'ot_surgery_type_id' => [
                'required',
                'integer',
                Rule::exists('ot_surgery_types', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'diagnosis_hint' => ['nullable', 'string', 'max:255'],
        ]);

        $surgeryType = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->where('id', (int) $validated['ot_surgery_type_id'])
            ->first(['id', 'surgery_name']);

        abort_if(! $surgeryType, 422, 'Invalid surgery type for this tenant.');

        $terminalStatuses = [OtBooking::STATUS_OPERATED, OtBooking::STATUS_DISCHARGED];
        $updatableStatuses = [OtBooking::STATUS_BOOKED, OtBooking::STATUS_SURGERY_RECOMMENDED];

        $existing = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('patient_id', $patient->id)
            ->whereNotIn('ot_status', $terminalStatuses)
            ->orderByDesc('id')
            ->first();

        if ($existing && ! in_array($existing->ot_status, $updatableStatuses, true)) {
            return redirect()
                ->back()
                ->with('error', 'This patient already has an active OT booking ('.$existing->ot_status.'). Complete or discharge it before recommending again.');
        }

        // Date/slot and staff are assigned later (counsellor / ward) — not at recommend.
        $payload = [
            'surgery_date' => null,
            'slot_id' => null,
            'eye' => $validated['eye'],
            'ot_type' => $surgeryType->surgery_name,
            'ot_status' => OtBooking::STATUS_SURGERY_RECOMMENDED,
        ];

        if ($existing) {
            $existing->update($payload);
            $booking = $existing->fresh();
            $message = 'Surgery recommendation updated. Patient is in the Counsellor queue.';
        } else {
            $booking = OtBooking::create([
                'tenant_id' => $tenantId,
                'patient_id' => $patient->id,
                'booked_by' => (int) auth('hospital_user')->id(),
                'ot_doctor_id' => null,
                'ot_assistant_id' => null,
                ...$payload,
            ]);
            $message = 'Surgery recommended. Patient sent to Counsellor queue.';
        }

        // Optional diagnosis hint → seed counselling row for counsellor form.
        $hint = trim((string) ($validated['diagnosis_hint'] ?? ''));
        if ($hint !== '') {
            OtCounselling::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'ot_booking_id' => $booking->id,
                ],
                [
                    'diagnosis' => $hint,
                    'created_by' => (int) auth('hospital_user')->id(),
                ]
            );
        }

        return redirect()
            ->route('hospital.dashboard', ['slug' => $slug])
            ->with('success', $message);
    }
}
