<?php

/**
 * OtWardApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\OT\OtWardController (web).
 *          OT Workflow Upgrade — Phase 3 (Ward Module): pre-op vitals + eye-drop register.
 *          See docs/OT_WORKFLOW_UPGRADE_PRD.md §3 and docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md §7
 *          (FR-OT-26..28).
 *
 * PERMISSIONS: ot.ward.entry (read screen/vitals/eye-drops/verification-header),
 *              ot.preop.entry (store vitals — NOT ot.ward.entry, verified against
 *              routes/hospital.php), ot.dilation.track (store eye-drop).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalUser;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtDilationEntry;
use App\Models\Hospital\OT\OtPreOp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OtWardApiController extends Controller
{
    /** Statuses allowed to open / edit ward vitals (PDF Step 6-7) — same as web. */
    private const WARD_ALLOWED_STATUSES = [
        OtBooking::STATUS_PAYMENT_VERIFIED,
        OtBooking::STATUS_IN_WARD,
        OtBooking::STATUS_DILATED,
        OtBooking::STATUS_READY,
    ];

    /**
     * Mobile mirror of `OtAccountantController::wardIndex()` (web groups this
     * screen under the Accountant controller, but it's the Ward Entry Queue —
     * kept here since every other Ward endpoint already lives on this
     * controller). No mobile API endpoint existed for this queue before —
     * both apps previously had to fall back to a manual booking-ID entry
     * screen. See OT_WEB_PARITY_FIX_PRD.md §4.
     */
    public function bookings(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $bookings = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,location_id,first_name,middle_name,last_name,contact_no', 'patient.location:id,city,district,state', 'payments'])
            ->whereIn('ot_status', [OtBooking::STATUS_PAYMENT_VERIFIED, OtBooking::STATUS_IN_WARD, OtBooking::STATUS_READY])
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25));

        // payment_status is a computed accessor, not a real column — see the
        // matching comment in OtAccountantApiController::bookings(). Safe
        // here since `payments` is already eager-loaded above.
        $bookings->getCollection()->each(fn (OtBooking $b) => $b->append(['payment_status']));

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    /**
     * Mobile mirror of `OtAccountantController::markReadyForOt()` — same 3
     * validation checks, same error messages, same status transition
     * (`ot_status` → `ready`). Callable both from the queue's quick-action
     * button and the detail screen's "Ready to send to OT Assistant?" card.
     */
    public function markReady(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if (! in_array($booking->ot_status, [OtBooking::STATUS_PAYMENT_VERIFIED, OtBooking::STATUS_IN_WARD], true)) {
            return response()->json(['success' => false, 'message' => 'Only payment-verified or in-ward patients can be handed off to OT Assistant.'], 422);
        }

        $preOp = OtPreOp::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        if (! $preOp || $preOp->pre_op_status !== OtPreOp::STATUS_READY_FOR_SURGERY) {
            return response()->json(['success' => false, 'message' => 'Set Patient Status to Ready for OT and save before sending.'], 422);
        }

        if (empty($booking->ot_assistant_id)) {
            return response()->json(['success' => false, 'message' => 'Assign an OT Assistant on Patient Status before sending to OT.'], 422);
        }

        $booking->update([
            'ot_status' => OtBooking::STATUS_READY,
            'attended_at' => $booking->attended_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Patient is ready and handed off to OT Assistant.',
            'data' => ['ot_status' => $booking->fresh()->ot_status],
        ]);
    }

    public function vitals(string $slug, int $bookingId): JsonResponse
    {
        $booking = $this->findBooking($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $tenantId = (int) app('tenant')->id;

        $preOp = OtPreOp::query()
            ->with('enteredBy:id,name')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        // NOTE: ot_pre_op holds one row per booking (updateOrCreate on every save) —
        // there is no history table on the web side, so "history" here is just the
        // single current row, not a log. Flagging this because the plan doc originally
        // assumed a history list.
        return response()->json(['success' => true, 'data' => ['latest' => $preOp]]);
    }

    public function storeVitals(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->findBooking($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if (! in_array($booking->ot_status, self::WARD_ALLOWED_STATUSES, true)) {
            return response()->json(['success' => false, 'message' => 'Ward entry is only available after counsellor payment verification.'], 422);
        }

        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'bp' => ['nullable', 'string', 'max:20'],
            'pulse' => ['nullable', 'string', 'max:20'],
            'rbs' => ['nullable', 'numeric', 'between:0,999.9'],
            'temperature' => ['nullable', 'numeric', 'between:0,99.9'],
            'spo2' => ['nullable', 'numeric', 'between:0,100'],
            'hba1c' => ['nullable', 'numeric', 'between:0,99.9'],
            'pre_op_status' => ['required', Rule::in(OtPreOp::STATUSES)],
        ]);

        OtPreOp::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
            [
                ...$validated,
                'entered_by' => (int) auth('sanctum')->id(),
            ]
        );

        $message = 'Vitals recorded.';

        // Same "Patient Status" staff-assignment sub-flow as the web form: pass
        // assign_staff=true to also set ot_doctor_id / ot_assistant_id in one call.
        if ($request->boolean('assign_staff')) {
            $isReady = $validated['pre_op_status'] === OtPreOp::STATUS_READY_FOR_SURGERY;

            $staff = $request->validate([
                'ot_doctor_id' => [
                    $isReady ? 'nullable' : 'required',
                    'integer',
                    Rule::exists('hospital_users', 'id')->where(function ($q) use ($tenantId) {
                        $q->where('tenant_id', $tenantId)
                            ->where('status', 'active')
                            ->where(function ($inner) {
                                $inner->whereNotNull('doctor_type')
                                    ->orWhereExists(function ($sub) {
                                        $sub->selectRaw('1')
                                            ->from('roles')
                                            ->whereColumn('roles.id', 'hospital_users.role_id')
                                            ->where(function ($roleQ) {
                                                $roleQ->where('roles.slug', 'doctor')
                                                    ->orWhere('roles.name', 'doctor');
                                            });
                                    });
                            });
                    }),
                ],
                'ot_assistant_id' => [
                    $isReady ? 'required' : 'nullable',
                    'integer',
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
                'ot_doctor_id.required' => 'Select a doctor when the patient is not ready for OT (consultation).',
                'ot_assistant_id.required' => 'Select an OT Assistant when the patient is Ready for OT.',
            ]);

            $booking->update([
                'ot_doctor_id' => ! empty($staff['ot_doctor_id'])
                    ? (int) $staff['ot_doctor_id']
                    : $booking->ot_doctor_id,
                'ot_assistant_id' => ! empty($staff['ot_assistant_id'])
                    ? (int) $staff['ot_assistant_id']
                    : ($isReady ? null : $booking->ot_assistant_id),
            ]);

            $message = $isReady
                ? 'Status saved. Patient assigned to OT Assistant.'
                : 'Status saved. Patient assigned to doctor for consultation.';
        }

        if ($booking->ot_status === OtBooking::STATUS_PAYMENT_VERIFIED) {
            $booking->update([
                'ot_status' => OtBooking::STATUS_IN_WARD,
                'attended_at' => $booking->attended_at ?? now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['ot_status' => $booking->fresh()->ot_status],
        ]);
    }

    public function eyeDrops(string $slug, int $bookingId): JsonResponse
    {
        $booking = $this->findBooking($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $tenantId = (int) app('tenant')->id;

        $eyeDrops = OtDilationEntry::query()
            ->with('administeredBy:id,name')
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->orderBy('administered_at')
            ->orderBy('dose_number')
            ->get();

        return response()->json(['success' => true, 'data' => ['eye_drops' => $eyeDrops]]);
    }

    public function addEyeDrop(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $booking = $this->findBooking($bookingId);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if (! in_array($booking->ot_status, self::WARD_ALLOWED_STATUSES, true)) {
            return response()->json(['success' => false, 'message' => 'Ward entry is only available after counsellor payment verification.'], 422);
        }

        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'medicine_name' => ['required', 'string', 'max:150'],
            'eye' => ['required', Rule::in(['RE', 'LE'])],
            'dose_number' => ['required', 'integer', 'min:1', 'max:20'],
            'administered_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $entry = OtDilationEntry::query()->create([
            'tenant_id' => $tenantId,
            'ot_booking_id' => $booking->id,
            'administered_at' => $validated['administered_at'] ?? now(),
            'medicine_name' => $validated['medicine_name'],
            'eye' => $validated['eye'],
            'dose_number' => $validated['dose_number'],
            'is_instilled' => true,
            'administered_by' => (int) auth('sanctum')->id(),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Eye drop dose logged.',
            'data' => $entry->load('administeredBy:id,name'),
        ], 201);
    }

    /**
     * Convenience endpoint (FR-OT-28) — read-only header card data so mobile doesn't
     * need to stitch together booking + patient calls just to show UHID/name/surgery/eye.
     */
    public function verificationHeader(string $slug, int $bookingId): JsonResponse
    {
        $booking = $this->findBooking($bookingId, ['patient:id,patient_code,first_name,middle_name,last_name']);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uhid' => $booking->patient?->patient_code,
                'patient_name' => trim(collect([
                    $booking->patient?->first_name,
                    $booking->patient?->middle_name,
                    $booking->patient?->last_name,
                ])->filter()->implode(' ')),
                'surgery_type' => $booking->ot_type,
                'eye' => $booking->eye,
            ],
        ]);
    }

    private function findBooking(int $bookingId, array $with = []): ?OtBooking
    {
        $tenantId = (int) app('tenant')->id;

        return OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->when($with, fn ($q) => $q->with($with))
            ->find($bookingId);
    }
}
