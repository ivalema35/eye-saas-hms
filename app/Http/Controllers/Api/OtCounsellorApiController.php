<?php

/**
 * OtCounsellorApiController.php
 *
 * PURPOSE: Mobile/tablet API mirror of Hospital\OT\OtCounsellorController (web).
 *          OT Workflow Upgrade — Phase 1 (Counsellor Module). See docs/OT_WORKFLOW_UPGRADE_PRD.md §1
 *          and docs/ROUND3_OT_MOBILE_API_PRD_PLAN.md §5 (FR-OT-18..21).
 *
 * PERMISSIONS: ot.counselling.fill (dashboard/form/counselling save/send-to-billing),
 *              ot.consent.capture (consent save) — verified against routes/hospital.php,
 *              not the shipped OT_WORKFLOW_UPGRADE_MOBILE_API_PRD.md (which names different keys).
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\OT\OtBooking;
use App\Models\Hospital\OT\OtConsent;
use App\Models\Hospital\OT\OtCounselling;
use App\Models\Hospital\OT\OtPackageMaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OtCounsellorApiController extends Controller
{
    public function bookings(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $bookings = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->whereIn('ot_status', [OtBooking::STATUS_BOOKED, OtBooking::STATUS_SURGERY_RECOMMENDED])
            ->orderByRaw('CASE WHEN ot_status = ? THEN 0 ELSE 1 END', [OtBooking::STATUS_SURGERY_RECOMMENDED])
            ->orderBy('surgery_date')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    /**
     * `package_id`-based (not `lens_cost`+`room_category`) — matches web
     * exactly (`OtCounsellorController::lookupPackage()`, web pull
     * 2026-08-07). Packages no longer carry a meaningful `lens_cost` (all
     * default to 0 on the new package-master form), so an exact-match
     * lookup on that column would collide across distinct packages — see
     * WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §3.
     */
    public function lookupPackage(Request $request): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $validated = $request->validate([
            'package_id' => [
                'required', 'integer',
                Rule::exists('ot_package_masters', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('is_active', true)->whereNull('deleted_at')),
            ],
        ]);

        $package = OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereKey((int) $validated['package_id'])
            ->first([
                'package_name',
                'room_category',
                'ot_charges',
                'surgeon_charges',
                'nursing_charges',
                'consumables_charges',
            ]);

        if (! $package) {
            return response()->json(['success' => true, 'data' => ['found' => false]]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'found' => true,
                'package' => [
                    'package_name' => $package->package_name,
                    'room_category' => $package->room_category,
                    'ot_charges' => (float) $package->ot_charges,
                    'surgeon_charges' => (float) $package->surgeon_charges,
                    'nursing_charges' => (float) $package->nursing_charges,
                    'consumables_charges' => (float) $package->consumables_charges,
                ],
            ],
        ]);
    }

    public function show(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $booking = OtBooking::query()
            ->where('tenant_id', $tenantId)
            ->with(['patient:id,patient_code,first_name,middle_name,last_name,contact_no', 'otDoctor:id,name'])
            ->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $counselling = OtCounselling::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $consent = OtConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        // Web pull 2026-08-07: no longer filtered to whereNotNull('lens_cost')
        // or ordered/selecting that column — packages no longer carry a
        // meaningful lens_cost (see lookupPackage()'s comment above).
        $packageCostOptions = OtPackageMaster::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('package_name')
            ->orderBy('room_category')
            ->get([
                'id',
                'package_name',
                'room_category',
                'ot_charges',
                'surgeon_charges',
                'nursing_charges',
                'consumables_charges',
            ]);

        $otSurgeryTypes = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->orderBy('surgery_name')
            ->get(['id', 'surgery_name']);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'counselling' => $counselling,
                'consent' => $consent,
                'package_cost_options' => $packageCostOptions,
                'ot_surgery_types' => $otSurgeryTypes,
            ],
        ]);
    }

    public function storeCounselling(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        // ot_type replaces surgery_type_confirmed as the real field — matches
        // web exactly (web pull 2026-08-07). Fallback keeps an
        // already-set-but-since-deactivated type valid, same as web.
        $surgeryTypeNames = DB::table('ot_surgery_types')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->pluck('surgery_name')
            ->filter()
            ->values()
            ->all();

        if ($booking->ot_type && ! in_array($booking->ot_type, $surgeryTypeNames, true)) {
            $surgeryTypeNames[] = $booking->ot_type;
        }

        $validated = $request->validate([
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'eye' => ['required', Rule::in(['RE', 'LE', 'Both'])],
            'ot_type' => ['required', 'string', 'max:150', Rule::in($surgeryTypeNames)],
            'mediclaim' => ['required', 'boolean'],
            'lens_category' => ['nullable', Rule::in(['standard', 'premium'])],
            'lens_company' => ['nullable', 'string', 'max:150'],
            'lens_model' => ['nullable', 'string', 'max:150'],
            'lens_type' => ['nullable', 'string', 'max:100'],
            'estimated_power' => ['nullable', 'numeric', 'between:-99.99,999.99'],
            'lens_cost' => ['nullable', 'numeric', 'min:0'],
            'package_name' => ['nullable', 'string', 'max:150'],
            'room_category' => ['nullable', Rule::in(['general', 'private'])],
            'ot_charges' => ['nullable', 'numeric', 'min:0'],
            'surgeon_charges' => ['nullable', 'numeric', 'min:0'],
            'nursing_charges' => ['nullable', 'numeric', 'min:0'],
            'consumables_charges' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => [
                'nullable',
                Rule::in(
                    filter_var($request->input('mediclaim'), FILTER_VALIDATE_BOOLEAN)
                        ? ['mediclaim']
                        : ['cash', 'online']
                ),
            ],
            'report_ok' => ['nullable', 'boolean'],
            'blood_reports_verified' => ['required', 'boolean'],
            'blood_reports_normal' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Sums all 4 charge fields + lens_cost — matches web exactly (web
        // pull 2026-08-07 changed this from lens_cost-only). See
        // WEB_PULL_2026_08_07_APP_PARITY_AUDIT.md §7.
        $totalEstimate = round(
            (float) ($validated['ot_charges'] ?? 0)
            + (float) ($validated['surgeon_charges'] ?? 0)
            + (float) ($validated['nursing_charges'] ?? 0)
            + (float) ($validated['consumables_charges'] ?? 0)
            + (float) ($validated['lens_cost'] ?? 0),
            2
        );
        $counsellorId = (int) auth('sanctum')->id();

        DB::transaction(function () use ($tenantId, $booking, $validated, $totalEstimate, $counsellorId): void {
            OtCounselling::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
                [
                    'diagnosis' => $validated['diagnosis'] ?? null,
                    'surgery_type_confirmed' => true,
                    'mediclaim' => (bool) $validated['mediclaim'],
                    'lens_category' => $validated['lens_category'] ?? null,
                    'lens_company' => $validated['lens_company'] ?? null,
                    'lens_model' => $validated['lens_model'] ?? null,
                    'lens_type' => $validated['lens_type'] ?? null,
                    'estimated_power' => $validated['estimated_power'] ?? null,
                    'lens_cost' => $validated['lens_cost'] ?? null,
                    'package_amount' => $totalEstimate,
                    'package_name' => $validated['package_name'] ?? null,
                    'room_category' => $validated['room_category'] ?? null,
                    'ot_charges' => $validated['ot_charges'] ?? null,
                    'surgeon_charges' => $validated['surgeon_charges'] ?? null,
                    'nursing_charges' => $validated['nursing_charges'] ?? null,
                    'consumables_charges' => $validated['consumables_charges'] ?? null,
                    'total_estimate' => $totalEstimate,
                    'payment_mode' => $validated['payment_mode'] ?? null,
                    'report_ok' => (bool) ($validated['report_ok'] ?? false),
                    'blood_reports_verified' => (bool) $validated['blood_reports_verified'],
                    'blood_reports_normal' => (bool) $validated['blood_reports_normal'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $counsellorId,
                    'counselled_by' => $counsellorId,
                    'counselled_at' => now(),
                ]
            );

            // Keep denormalized booking columns in sync — web's OtAccountantController
            // and other OT screens read these directly off ot_bookings.
            $booking->update([
                'eye' => $validated['eye'],
                'ot_type' => $validated['ot_type'],
                'has_mediclaim' => (bool) $validated['mediclaim'],
                'package_amount' => $totalEstimate,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'reports_ok' => (bool) ($validated['report_ok'] ?? false),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Counselling details saved.',
            'data' => [
                'total_estimate' => $totalEstimate,
                'counselling' => $booking->refresh()->counselling,
            ],
        ]);
    }

    public function storeConsent(string $slug, Request $request, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $validated = $request->validate([
            'consent_given' => ['required', 'boolean'],
            'patient_signature' => ['nullable', 'string'],
            'guardian_signature' => ['nullable', 'string'],
            'witness_name' => ['nullable', 'string', 'max:150'],
        ]);

        $existing = OtConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->first();

        $patientSignaturePath = $existing?->patient_signature_path;
        $guardianSignaturePath = $existing?->guardian_signature_path;

        if (! empty($validated['patient_signature']) && str_starts_with($validated['patient_signature'], 'data:image')) {
            $patientSignaturePath = $this->storeSignature($tenantId, $booking->id, 'patient', $validated['patient_signature']);
        }

        if (! empty($validated['guardian_signature']) && str_starts_with($validated['guardian_signature'], 'data:image')) {
            $guardianSignaturePath = $this->storeSignature($tenantId, $booking->id, 'guardian', $validated['guardian_signature']);
        }

        $consent = OtConsent::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'ot_booking_id' => $booking->id],
            [
                'consent_given' => (bool) $validated['consent_given'],
                'patient_signature_path' => $patientSignaturePath,
                'guardian_signature_path' => $guardianSignaturePath,
                'witness_name' => $validated['witness_name'] ?? null,
                'consent_date' => (bool) $validated['consent_given'] ? now() : null,
                'created_by' => (int) auth('sanctum')->id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Consent recorded successfully.',
            'data' => [
                'consent_given' => $consent->consent_given,
                'patient_signature_url' => $patientSignaturePath ? Storage::disk('public')->url($patientSignaturePath) : null,
                'guardian_signature_url' => $guardianSignaturePath ? Storage::disk('public')->url($guardianSignaturePath) : null,
                'witness_name' => $consent->witness_name,
                'consent_date' => $consent->consent_date,
            ],
        ]);
    }

    public function sendToBilling(string $slug, int $bookingId): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $booking = OtBooking::query()->where('tenant_id', $tenantId)->find($bookingId);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        $hasCounselling = OtCounselling::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->exists();

        $consentGiven = OtConsent::query()
            ->where('tenant_id', $tenantId)
            ->where('ot_booking_id', $booking->id)
            ->where('consent_given', true)
            ->exists();

        if (! $hasCounselling) {
            return response()->json(['success' => false, 'message' => 'Save counselling details before sending to billing.'], 422);
        }

        if (! $consentGiven) {
            return response()->json(['success' => false, 'message' => 'Patient consent must be captured before sending to billing.'], 422);
        }

        $packageAmount = (float) ($booking->package_amount ?? 0);
        if ($packageAmount <= 0) {
            return response()->json(['success' => false, 'message' => 'Package / cost estimate must be greater than zero before sending to billing.'], 422);
        }

        $booking->update(['ot_status' => OtBooking::STATUS_COUNSELLED]);

        return response()->json([
            'success' => true,
            'message' => 'Patient sent to Billing.',
            'data' => ['ot_status' => $booking->ot_status],
        ]);
    }

    private function storeSignature(int $tenantId, int $bookingId, string $type, string $base64): string
    {
        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $imageData = base64_decode($base64Data);

        $path = "tenants/{$tenantId}/ot-consents/{$bookingId}/{$type}_signature_" . time() . '.png';
        Storage::disk('public')->put($path, $imageData);

        return $path;
    }
}
