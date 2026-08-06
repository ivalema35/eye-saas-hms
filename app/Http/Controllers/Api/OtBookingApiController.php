<?php

/**
 * OtBookingApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\OT\OtBookingController::recommendSurgery()
 * and Hospital\OT\OtReceptionistController::dashboard() — found missing during a
 * full app-wide web-vs-API parity audit (2026-08-04), after Round 3 (OT Workflow
 * Upgrade Phases 1-8) shipped. This is the single most important gap found: it's
 * the entry point that actually CREATES an OtBooking (surgery_recommended status)
 * from the doctor's exam screen — without it, nothing built in Phases 1-8 is
 * reachable from mobile, since there'd be no booking to counsel/ward/operate on.
 *
 * `OtBookingController::index()/create()/store()` are NOT mirrored — on the web
 * they are dead redirect stubs (manual OT booking was retired in favor of
 * Appointment + Doctor Recommend Surgery), there's no real logic to mirror.
 *
 * PERMISSIONS: ot.surgery.recommend (recommendSurgery), ot.patient.list (dashboard).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtCounselling;
use App\Models\Hospital\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtBookingApiController extends Controller
{
    public function recommendSurgery(string $slug, Request $request, int $patientId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $patient = Patient::query()->where('tenant_id', $tenantId)->find($patientId);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found.'], 404);
        }

        $validated = $request->validate([
            'eye' => ['required', Rule::in(['RE', 'LE', 'Both'])],
            'surgery_date' => ['required', 'date', 'after_or_equal:today'],
            'slot_id' => [
                'required', 'integer',
                Rule::exists('tbl_ot_slots', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'ot_surgery_type_id' => [
                'required', 'integer',
                Rule::exists('ot_surgery_types', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'diagnosis_hint' => ['nullable', 'string', 'max:255'],
        ]);

        $surgeryType = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->where('id', (int) $validated['ot_surgery_type_id'])
            ->first(['id', 'surgery_name']);

        if (! $surgeryType) {
            return response()->json(['success' => false, 'message' => 'Invalid surgery type for this tenant.'], 422);
        }

        $terminalStatuses = [OtBooking::STATUS_OPERATED, OtBooking::STATUS_DISCHARGED];
        $updatableStatuses = [OtBooking::STATUS_BOOKED, OtBooking::STATUS_SURGERY_RECOMMENDED];

        $existing = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->where('patient_id', $patient->id)
            ->whereNotIn('ot_status', $terminalStatuses)
            ->orderByDesc('id')
            ->first();

        if ($existing && ! in_array($existing->ot_status, $updatableStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => "This patient already has an active OT booking ({$existing->ot_status}). Complete or discharge it before recommending again.",
            ], 422);
        }

        $payload = [
            'surgery_date' => $validated['surgery_date'],
            'slot_id' => (int) $validated['slot_id'],
            'eye' => $validated['eye'],
            'ot_type' => $surgeryType->surgery_name,
            'ot_status' => OtBooking::STATUS_SURGERY_RECOMMENDED,
        ];

        $userId = (int) auth('sanctum')->id();

        if ($existing) {
            $existing->update($payload);
            $booking = $existing->fresh();
            $message = 'Surgery recommendation updated. Patient is in the Counsellor queue.';
        } else {
            $booking = OtBooking::create([
                'tenant_id' => $tenantId,
                'patient_id' => $patient->id,
                'booked_by' => $userId,
                'ot_doctor_id' => null,
                'ot_assistant_id' => null,
                ...$payload,
            ]);
            $message = 'Surgery recommended. Patient sent to Counsellor queue.';
        }

        $hint = trim((string) ($validated['diagnosis_hint'] ?? ''));
        if ($hint !== '') {
            OtCounselling::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
                ['diagnosis' => $hint, 'created_by' => $userId]
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['booking_id' => $booking->id, 'ot_status' => $booking->ot_status],
        ], 201);
    }

    public function receptionistDashboard(): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $totalOtToday = DB::table('ot_bookings')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereDate('surgery_date', today())
            ->count();

        $pendingCounselling = DB::table('ot_bookings')
            ->where('tenant_id', $tenantId)
            ->whereDate('surgery_date', '>=', today())
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('ot_counselling')
                    ->whereColumn('ot_counselling.ot_booking_id', 'ot_bookings.id');
            })
            ->count();

        $readyForSurgery = DB::table('ot_bookings')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('ot_status', 'ready')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_ot_today' => $totalOtToday,
                'pending_counselling' => $pendingCounselling,
                'ready_for_surgery' => $readyForSurgery,
            ],
        ]);
    }
}
